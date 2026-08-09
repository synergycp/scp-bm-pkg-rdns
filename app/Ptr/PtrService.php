<?php

namespace Packages\Rdns\App\Ptr;

use App\Entity\EntityFilterService;
use App\Entity\LookupService;
use App\Ip\IpService;

class PtrService
{
    /**
     * @var LookupService
     */
    private $lookup;

    /**
     * @var IpService
     */
    private $ips;

    /**
     * @var PtrRepository
     */
    private $ptrs;

    /**
     * @var EntityFilterService
     */
    private $entityFilter;

    /**
     * PtrService constructor.
     *
     * @param LookupService       $lookup
     * @param IpService           $ips
     * @param PtrRepository       $ptrs
     * @param EntityFilterService $entityFilter
     */
    public function __construct(
        LookupService $lookup,
        IpService $ips,
        PtrRepository $ptrs,
        EntityFilterService $entityFilter
    ) {
        $this->lookup = $lookup;
        $this->ips = $ips;
        $this->ptrs = $ptrs;
        $this->entityFilter = $entityFilter;
    }

    /**
     * @param string $ip
     * @param string $point
     *
     * @return Ptr
     */
    public function create($ip, $point)
    {
        if ($ptr = $this->ptrs->byIp($ip)) {
            if ($ptr->ptr !== $point) {
                $ptr->ptr = $point;
                $ptr->save();

                event(new Events\PtrPtrUpdated($ptr));
            }

            return $ptr;
        }

        $ptr = $this->ptrs->make();
        $ptr->ip = $ip;
        $ptr->ptr = $point;
        $ptr->entity_id = $this->getEntityId($ip);
        $ptr->save();

        event(new Events\PtrCreated($ptr));

        return $ptr;
    }

    /**
     * Bulk-import PTR records with a fixed number of queries instead of
     * several per record (existence check, entity lookup, and insert are
     * each done once per import rather than once per IP).
     *
     * @param array<string, string> $ipToPtr presentation-form IP => PTR hostname
     *
     * @return array{created: int, updated: int, unchanged: int}
     */
    public function createMany(array $ipToPtr)
    {
        $records = [];

        foreach ($ipToPtr as $ip => $point) {
            $bin = inet_pton($ip);

            if ($bin === false) {
                continue;
            }

            // Prefix the key so PHP never casts numeric-looking strings.
            $records['k' . bin2hex($bin)] = ['bin' => $bin, 'ptr' => $point];
        }

        $result = ['created' => 0, 'updated' => 0, 'unchanged' => 0];

        if (!$records) {
            return $result;
        }

        // One query per chunk: find the PTRs that already exist.
        foreach (array_chunk(array_column($records, 'bin'), 5000) as $chunk) {
            $existing = $this->ptrs->query()->whereIn('ip', $chunk)->get();

            foreach ($existing as $ptr) {
                $key = 'k' . bin2hex(inet_pton($ptr->ip));

                if (!isset($records[$key])) {
                    continue;
                }

                $point = $records[$key]['ptr'];
                unset($records[$key]);

                if ($ptr->ptr === $point) {
                    $result['unchanged']++;
                    continue;
                }

                $ptr->ptr = $point;
                $ptr->save();

                event(new Events\PtrPtrUpdated($ptr));

                $result['updated']++;
            }
        }

        if (!$records) {
            return $result;
        }

        $entityIds = $this->entityIds($records);

        $rows = [];
        foreach ($records as $key => $record) {
            $rows[] = [
                'ip' => $record['bin'],
                'ptr' => $record['ptr'],
                'entity_id' => isset($entityIds[$key]) ? $entityIds[$key] : null,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            $this->ptrs->query()->insert($chunk);
        }

        $result['created'] = count($rows);

        // Re-fetch the inserted rows so creation events carry real models.
        foreach (array_chunk(array_column($records, 'bin'), 5000) as $chunk) {
            $this->ptrs->query()
                ->whereIn('ip', $chunk)
                ->get()
                ->each(function (Ptr $ptr) {
                    event(new Events\PtrCreated($ptr));
                });
        }

        return $result;
    }

    /**
     * Resolve the owning Entity for each record with one entities query per
     * IP family (a min-max bounding range) instead of one query per IP,
     * then match precisely in PHP the way Entity::scopeHasIpRange() does.
     *
     * @param array<string, array{bin: string, ptr: string}> $records
     *
     * @return array<string, int> record key => entity id
     */
    private function entityIds(array $records)
    {
        $families = [];

        foreach ($records as $key => $record) {
            $families[strlen($record['bin'])][$key] = $record['bin'];
        }

        $entityIds = [];

        foreach ($families as $bins) {
            $min = $max = null;

            foreach ($bins as $bin) {
                // strcmp, not min()/max(): PHP compares numeric-looking
                // strings numerically, which breaks byte ordering.
                if ($min === null || strcmp($bin, $min) < 0) {
                    $min = $bin;
                }
                if ($max === null || strcmp($bin, $max) > 0) {
                    $max = $bin;
                }
            }

            $range = $this->ips->range(
                $this->ips->make(inet_ntop($min)),
                $this->ips->make(inet_ntop($max))
            );

            $query = $this->lookup->overlapping($range);
            $this->entityFilter->viewable($query);

            $candidates = $query
                ->orderBy('id')
                ->get()
                ->map(function ($entity) {
                    return [
                        'id' => $entity->getKey(),
                        'ip' => $entity->getRawOriginal('ip'),
                        'range_end' => $entity->getRawOriginal('range_end'),
                        'gateway' => $entity->getRawOriginal('gateway'),
                    ];
                });

            foreach ($bins as $key => $bin) {
                foreach ($candidates as $candidate) {
                    if ($this->entityCovers($candidate, $bin)) {
                        $entityIds[$key] = $candidate['id'];
                        break;
                    }
                }
            }
        }

        return $entityIds;
    }

    /**
     * PHP mirror of Entity::scopeHasIpRange() for a single address.
     *
     * @param array  $entity
     * @param string $bin
     *
     * @return bool
     */
    private function entityCovers(array $entity, $bin)
    {
        $len = strlen($bin);

        if (
            is_string($entity['ip']) && strlen($entity['ip']) === $len &&
            is_string($entity['range_end']) && strlen($entity['range_end']) === $len &&
            strcmp($entity['ip'], $bin) <= 0 &&
            strcmp($entity['range_end'], $bin) >= 0
        ) {
            return true;
        }

        return is_string($entity['gateway']) &&
            strlen($entity['gateway']) === $len &&
            $entity['gateway'] === $bin;
    }

    /**
     * @param string $ip
     *
     * @return int|null
     */
    public function getEntityId($ip)
    {
        if (!$range = $this->ips->make($ip)) {
            abort(400, 'Invalid IP address: ' . e($ip));
        }

        $entityQuery = $this->lookup->overlapping($range);

        $this->entityFilter->viewable($entityQuery);

        if (!$entity = $entityQuery->first()) {
            return null;
        }

        return $entity->getKey();
    }
}
