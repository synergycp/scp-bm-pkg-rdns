<?php

namespace Packages\Rdns\App\Ptr;

use App\Database\ModelRepository;
use App\Ip\IpService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Store Ptrs in and retrieve them from the database.
 */
class PtrRepository
extends ModelRepository
{
    protected $model = Ptr::class;

    /**
     * @var IpService
     */
    private $ip;

    /**
     * PtrRepository constructor.
     *
     * @param IpService $ip
     */
    public function boot(IpService $ip)
    {
        $this->ip = $ip;
    }

    /**
     * @param string $ip
     *
     * @return Ptr|void
     */
    public function byIp($ip)
    {
        $addr = $this->ip->make($ip);

        if (!$addr) {
            return;
        }

        return $this
            ->query()
            ->where('ip', $addr->forDB())
            ->first()
        ;
    }

    /**
     * @param Builder $query
     * @param string  $joinType
     */
    public function joinEntity(Builder $query, $joinType = 'inner')
    {
        return $query->join(
            'entities',
            'entities.id', '=', $this->getTable().'.entity_id',
            $joinType
        );
    }
}
