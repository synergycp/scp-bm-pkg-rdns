<?php

namespace Packages\Rdns\App\Ptr\Listeners;

use App\Entity\Owner\Events\EntityOwnerDeleted;
use Packages\Rdns\App\Ptr\Events\PtrDeleted;
use Packages\Rdns\App\Ptr\Ptr;
use Packages\Rdns\App\Ptr\PtrRepository;
use App\Entity\Entity;

class ClearRdns
{
    /**
     * @var PtrRepository
     */
    private $ptrs;

    public function __construct(PtrRepository $ptrs)
    {
        $this->ptrs = $ptrs;
    }

    public function handle(EntityOwnerDeleted $event)
    {
        $this->clear($event->target);
    }

    private function clear(Entity $entity)
    {
        $this->ptrs
            ->where('entity_id', $entity->getKey())
            ->each(function (Ptr $ptr) {
                event(new PtrDeleted($ptr));
            })
            ;
    }

}
