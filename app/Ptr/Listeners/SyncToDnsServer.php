<?php

namespace Packages\Rdns\App\Ptr\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Packages\Rdns\App\Ptr\Events\PtrEvent;
use Packages\Rdns\App\Server\ServerService;

class SyncToDnsServer
implements ShouldQueue
{
    /**
     * @var ServerService
     */
    private $server;

    /**
     * SyncToDnsServer constructor.
     *
     * @param ServerService $server
     */
    public function __construct(ServerService $server)
    {
        $this->server = $server;
    }

    public function handle(PtrEvent $event)
    {
        $this->server->get()->createPtr(
            $event->target->ip,
            $event->target->ptr
        );
    }
}
