<?php

namespace Packages\Rdns\App\Ptr\Events;

use App\Log\Log;

class PtrPtrUpdated extends PtrLoggableEvent
{
    public function log(Log $log)
    {
        $log->setDesc("Ptr updated: {$this->target->ip} -> {$this->target->ptr}")
            ->setTarget($this->target)
            ->save()
            ;
    }
}
