<?php

namespace Packages\Rdns\App\Ptr\Listeners;

use App\Entity\Events\EntityDeleted;

class ClearRdnsOnDelete extends ClearRdns
{
    public function handle(EntityDeleted $event)
    {
        $this->clear($event->target);
    }
}
