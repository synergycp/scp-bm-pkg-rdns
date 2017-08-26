<?php

namespace Packages\Rdns\App\Ptr\Events;

use Packages\Rdns\App\Ptr;
use App\Support\Event;
use App\Support\Database\SerializesModels;

/**
 * Base Ptr Event.
 */
abstract
class PtrEvent
extends Event
{
    use SerializesModels;

    /**
     * @var Ptr\Ptr
     */
    public $target;

    /**
     * Create a new event instance.
     *
     * @param Ptr\Ptr $target
     */
    public function __construct(Ptr\Ptr $target)
    {
        $this->target = $target;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
