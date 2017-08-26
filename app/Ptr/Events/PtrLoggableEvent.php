<?php

namespace Packages\Rdns\App\Ptr\Events;

use App\Log;

/**
 * Base Ptr Loggable Event.
 */
abstract
class PtrLoggableEvent
extends PtrEvent
implements Log\LoggableEvent
{
    abstract public function log(Log\Log $log);
}
