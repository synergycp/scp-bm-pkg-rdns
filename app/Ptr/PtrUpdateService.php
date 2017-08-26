<?php

namespace Packages\Rdns\App\Ptr;

use App\Entity\LookupService;
use App\Ip\IpService;
use App\Support\Http\UpdateService;
use Illuminate\Support\Collection;

class PtrUpdateService
    extends UpdateService
{
    /**
     * @var PtrFormRequest
     */
    protected $request;
    protected $requestClass = PtrFormRequest::class;

    /**
     * @var LookupService
     */
    private $lookup;

    /**
     * @var IpService
     */
    private $ips;

    /**
     * SyncToDnsServer constructor.
     *
     * @param LookupService $lookup
     * @param IpService     $ips
     */
    public function boot(LookupService $lookup, IpService $ips)
    {
        $this->lookup = $lookup;
        $this->ips = $ips;
    }

    public function afterCreate(Collection $items)
    {
        $createEvent = $this->queueHandler(
            Events\PtrCreated::class
        );

        $this->successItems(
            'pkg.rdns::ptr.created',
            $items->each($createEvent)
        );
    }

    protected function updateAll(Collection $items)
    {
        $this->setPtr($items);
    }

    private function setPtr(Collection $items)
    {
        $inputs = [
            'ptr' => $this->input('ptr') ?: null,
        ];
        $updateEvent = $this->queueHandler(
            Events\PtrPtrUpdated::class
        );

        $this->successItems(
            'pkg.rdns::ptr.changed',
            $items
                ->filter($this->changed($inputs))
                ->reject([$this, 'isCreating'])
                ->each($updateEvent),
            ['field' => 'PTR Record']
        );
    }

    protected function beforeCreate(Collection $items)
    {
        $this->setIp($items);
    }

    private function setIp(Collection $items)
    {
        $inputs = [
            'ip' => $ip = $this->input('ip'),
            'entity_id' => $this->getEntityId($ip),
        ];

        $this->successItems(
            'pkg.rdns::ptr.changed',
            $items
                ->filter($this->changed($inputs))
                ->reject([$this, 'isCreating']),
            ['field' => 'IP']
        );
    }

    /**
     * @param string $ip
     *
     * @return int|null
     */
    private function getEntityId($ip)
    {
        $range = $this->ips->make($ip);
        $entity = $this
            ->lookup
            ->overlapping($range)
            ->get()
            ->first()
        ;

        if (!$entity) {
            return null;
        }

        return $entity->getKey();
    }
}
