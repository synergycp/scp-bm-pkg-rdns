<?php

namespace Packages\Rdns\App\Ptr;

use App\Entity\Events\EntityDeleted;
use App\Log\EventLogger;
use App\Support\EventServiceProvider;
use Packages\Rdns\App\Ptr\Listeners;
use App\Ip\Owner\Events\IPOwnerDeleted;
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
    IPOwnerDeleted::class => [
      ClearRdns::class,
    ],
    EntityDeleted::class => [
      Listeners\ClearRdnsOnDelete::class,
    ],
  ];
}
