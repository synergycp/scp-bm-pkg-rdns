<?php

namespace Packages\Rdns\App\Ptr\Listeners;

use Packages\Rdns\App\Ptr\Events\PtrDeleted;
use Packages\Rdns\App\Ptr\Ptr;
use Packages\Rdns\App\Ptr\PtrRepository;
use App\Entity\Entity;
use App\Ip\Owner\Events\IPOwnerDeleted;
use App\Ip\Owner\IIPHasOwner;

class ClearRdns
{
  /**
   * @var PtrRepository
   */
  private $ptrs;

  public function __construct(PtrRepository $ptrs) {
    $this->ptrs = $ptrs;
  }

  public function handle($event) {
    $this->clear($event->target);
  }

  protected function clear(IIPHasOwner $ip) {
    if (get_class($ip->ipHasOwnerModelForLogging()) != Entity::class) {
      return;
    }
    $this->ptrs
      ->where('entity_id', $ip->ipHasOwnerModelForLogging()->getKey())
      ->each(function (Ptr $ptr) {
        event(new PtrDeleted($ptr));
        $ptr->forceDelete();
      });
  }
}
