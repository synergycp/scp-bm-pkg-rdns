<?php

namespace Packages\Rdns\App\Ptr\Listeners;

use App\Log\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
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
        $result = $this->server->get()->createPtr(
            $event->target->ip,
            $event->target->ptr
        );

        if (is_string($result) && $result !== '') {
            $this->appendToLog($event, $result);
        }
    }

    private function appendToLog(PtrEvent $event, string $info)
    {
        $log = Log::query()
            ->whereHas('targets', function ($q) use ($event) {
                $q->where('target_type', get_class($event->target))
                  ->where('target_id', $event->target->getKey());
            })
            ->where('desc', 'like', 'Ptr created:%')
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
