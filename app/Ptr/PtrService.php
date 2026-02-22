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
            $ptr->ptr = $point;
            $ptr->save();

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
