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

    public function __construct(Ptr\Ptr $target)
    {
        parent::__construct($target);

        $this->targetIp = $target->ip;
    }

    public function log(Log $log)
    {
        $log->setDesc('Ptr deleted')
            ->setTarget($this->target)
            ->save()
            ;
    }
}
