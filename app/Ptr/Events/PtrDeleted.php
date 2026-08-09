<?php

namespace Packages\Rdns\App\Ptr\Events;

use App\Log\Log;
use Packages\Rdns\App\Ptr;

class PtrDeleted extends PtrLoggableEvent
{
    protected $allowNullModel = true;

    /**
     * @var Ptr\Ptr|null
     */
    public $target;

    /**
     * @var string
     */
    public $targetIp;

    /**
     * @var string|null
     */
    public $targetPtr;

    public function __construct(Ptr\Ptr $target)
    {
        parent::__construct($target);

        // Capture scalars: the model may be gone (and $target null) by the time
        // queued listeners deserialize this event.
        $this->targetIp = $target->ip;
        $this->targetPtr = $target->ptr;
    }

    public function log(Log $log)
    {
        $log->setDesc("Ptr deleted: {$this->targetIp} -> {$this->targetPtr}");

        if ($this->target) {
            $log->setTarget($this->target);
        }

        $log->save();
    }
}
