<?php

namespace Packages\Rdns\App\Ptr\Events;

use App\Log\Log;

class PtrPtrUpdated extends PtrLoggableEvent
{
    public function log(Log $log)
    {
        $log->setDesc('Ptr ptr updated')
            ->setTarget($this->target)
            ->save()
            ;
    }
}
