<?php

namespace Packages\Rdns\App\Ptr\Events;

use App\Log\Log;

class PtrCreated extends PtrLoggableEvent
{
    public function log(Log $log)
    {
        $log->setDesc("Ptr created: {$this->target->ip} -> {$this->target->ptr}")
            ->setTarget($this->target)
            ->save()
            ;
    }
}
