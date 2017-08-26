<?php

namespace Packages\Rdns\App\Ptr;

use App\Log\EventLogger;
use App\Support\EventServiceProvider;
use Packages\Rdns\App\Ptr\Listeners;
use App\Entity\Owner\Events\EntityOwnerDeleted;
use Packages\Rdns\App\Ptr\Listeners\ClearRdns;

/**
 * Setup PTR Records Event Listeners.
 */
class PtrEventServiceProvider
extends EventServiceProvider
{
    protected $listen = [
        Events\PtrCreated::class => [
            EventLogger::class,
            Listeners\SyncToDnsServer::class,
        ],
        Events\PtrDeleted::class => [
            EventLogger::class,
            Listeners\DeleteFromDnsServer::class,
        ],
        Events\PtrPtrUpdated::class => [
            EventLogger::class,
            Listeners\SyncToDnsServer::class,
        ],
        EntityOwnerDeleted::class => [
            ClearRdns::class,
        ],
    ];
}
