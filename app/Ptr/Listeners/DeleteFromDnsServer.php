<?php

namespace Packages\Rdns\App\Ptr\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Packages\Rdns\App\Ptr\Events\PtrDeleted;
use Packages\Rdns\App\Server\ServerService;

class DeleteFromDnsServer
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

    public function handle(PtrDeleted $event)
    {
        $this->server->get()
                     ->deletePtr(
                         $event->targetIp
                     )
        ;
    }
}
