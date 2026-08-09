<?php

namespace Packages\Rdns\App\Ptr\Listeners;

use App\Log\Factory;
use App\Log\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Packages\Rdns\App\Ptr\Events\PtrEvent;
use Packages\Rdns\App\Server\ServerService;

class SyncToDnsServer
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
     * SyncToDnsServer constructor.
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

    public function handle(PtrEvent $event)
    {
        $result = $this->server->get()->createPtr(
            $event->target->ip,
            $event->target->ptr
        );

        if (is_string($result) && $result !== '') {
            $this->appendToLog($event, $result);
        }
    }

    /**
     * Make DNS sync failures visible to admins once retries are exhausted;
     * otherwise the panel and the DNS server drift silently.
     */
    public function failed(PtrEvent $event, \Throwable $exception)
    {
        $log = $this->logs->create(
            "Ptr sync to DNS failed: {$event->target->ip} -> {$event->target->ptr}"
        );

        $log->setTarget($event->target)
            ->setException($exception)
            ->save();
    }

    private function appendToLog(PtrEvent $event, string $info)
    {
        $log = Log::query()
            ->whereHas('targets', function ($q) use ($event) {
                $q->where('target_type', get_class($event->target))
                  ->where('target_id', $event->target->getKey());
            })
            ->where(function ($q) {
                $q->where('desc', 'like', 'Ptr created:%')
                  ->orWhere('desc', 'like', 'Ptr updated:%');
            })
            ->latest()
            ->first();

        if ($log) {
            $newDesc = "{$log->getAttributes()['desc']}. {$info}";
            $data = $log->data ?: [];
            $data['description'] = $newDesc;

            DB::table('logs')
                ->where('id', $log->id)
                ->update([
                    'desc' => substr($newDesc, 0, 100),
                    'data' => json_encode($data),
                ]);
        }
    }
}
