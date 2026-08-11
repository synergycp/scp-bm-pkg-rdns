<?php

namespace Packages\Rdns\App\Ptr;

use App\Entity\Entity;
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

        foreach ($families as $len => $bins) {
            // The parent entity lookup only matches the IPv4 columns, so
            // IPv6 records are matched against entities.v6_address instead.
            if ($len === 16) {
                $candidates = $this->ipv6EntityCandidates();

                foreach ($bins as $key => $bin) {
                    foreach ($candidates as $candidate) {
                        if ($this->ipv6RangeCovers($candidate['v6_address'], $bin)) {
                            $entityIds[$key] = $candidate['id'];
                            break;
                        }
                    }
                }

                continue;
            }

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

        // The parent entity lookup (scopeHasIpRange) only matches the IPv4
        // columns (ip/range_end/gateway); IPv6 assignments live in
        // entities.v6_address and are matched here.
        $binary = inet_pton($ip);
        if (is_string($binary) && strlen($binary) === 16) {
            foreach ($this->ipv6EntityCandidates() as $candidate) {
                if ($this->ipv6RangeCovers($candidate['v6_address'], $binary)) {
                    return $candidate['id'];
                }
            }

            return null;
        }

        $entityQuery = $this->lookup->overlapping($range);

        $this->entityFilter->viewable($entityQuery);

        if (!$entity = $entityQuery->first()) {
            return null;
        }

        return $entity->getKey();
    }

    /**
     * Entities with an IPv6 assignment, filtered to those viewable by the
     * current auth (so clients only match their own entities).
     *
     * @return array<int, array{id: int, v6_address: string}>
     */
    private function ipv6EntityCandidates()
    {
        $query = Entity::query()
            ->whereNotNull('entities.v6_address')
            ->where('entities.v6_address', '!=', '');

        $this->entityFilter->viewable($query);

        return $query
            ->orderBy('entities.id')
            ->get(['entities.id', 'entities.v6_address'])
            ->map(function ($entity) {
                return [
                    'id' => $entity->getKey(),
                    'v6_address' => (string) $entity->v6_address,
                ];
            })
            ->all();
    }

    /**
     * Whether an entities.v6_address value covers a binary IPv6 address.
     * The value is either a CIDR (2605:9f80::/64) or a bare address, which
     * only matches exactly.
     *
     * @param string $v6Address
     * @param string $bin 16-byte inet_pton form
     *
     * @return bool
     */
    private function ipv6RangeCovers($v6Address, $bin)
    {
        $parts = explode('/', trim($v6Address), 2);
        $base = inet_pton($parts[0]);

        if (!is_string($base) || strlen($base) !== 16) {
            return false;
        }

        $prefix = isset($parts[1]) ? (int) $parts[1] : 128;
        if ($prefix < 0 || $prefix > 128) {
            return false;
        }

        $bytes = intdiv($prefix, 8);
        $bits = $prefix % 8;

        if ($bytes > 0 && strncmp($bin, $base, $bytes) !== 0) {
            return false;
        }

        if ($bits === 0) {
            return true;
        }

        $mask = (0xff << (8 - $bits)) & 0xff;

        return (ord($bin[$bytes]) & $mask) === (ord($base[$bytes]) & $mask);
    }
}
