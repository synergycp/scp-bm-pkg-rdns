<?php

namespace Packages\Rdns\App\Ptr\Listeners;

use App\Log\Factory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Packages\Rdns\App\Ptr\Events\PtrDeleted;
use Packages\Rdns\App\Server\ServerService;

class DeleteFromDnsServer
    implements ShouldQueue
{
    /**
     * @var int
     */
    public $tries = 3;

    /**
     * @var ServerService
     */
    private $server;

    /**
     * @var Factory
     */
    private $logs;

    /**
     * DeleteFromDnsServer constructor.
     *
     * @param ServerService $server
     * @param Factory       $logs
     */
    public function __construct(ServerService $server, Factory $logs)
    {
        $this->server = $server;
        $this->logs = $logs;
    }

    /**
     * Seconds to wait before each retry.
     *
     * @return array<int>
     */
    public function backoff()
    {
        return [30, 120];
    }

    public function handle(PtrDeleted $event)
    {
        $this->server->get()
                     ->deletePtr(
                         $event->targetIp
                     )
        ;
    }

    /**
     * Make DNS delete failures visible to admins once retries are exhausted;
     * otherwise the stale PTR lingers on the DNS server silently.
     */
    public function failed(PtrDeleted $event, \Throwable $exception)
    {
        $log = $this->logs->create(
            "Ptr delete from DNS failed: {$event->targetIp}"
        );

        if ($event->target) {
            $log->setTarget($event->target);
        }

        $log->setException($exception)->save();
    }
}
