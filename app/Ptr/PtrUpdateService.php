<?php

namespace Packages\Rdns\App\Ptr;

use App\Support\Http\UpdateService;
use Illuminate\Support\Collection;

class PtrUpdateService extends UpdateService {
  /**
   * @var PtrFormRequest
   */
  protected $request;
  protected $requestClass = PtrFormRequest::class;

  /**
   * @var PtrService
   */
  private $ptr;

  /**
   * @var PtrRepository
   */
  private $ptrs;

    /**
   * @var PtrValidateRdns
   */
  private $ptrValidator;

  /**
   * PtrUpdateService constructor.
   *
   * @param PtrService    $ptr
   * @param PtrRepository $ptrs
   * @param PtrValidateRdns $ptrValidator
   */
  public function boot(PtrService $ptr, PtrRepository $ptrs, PtrValidateRdns $ptrValidator) {
    $this->ptr = $ptr;
    $this->ptrs = $ptrs;
    $this->ptrValidator = $ptrValidator;
  }

  public function afterCreate(Collection $items) {
    $createEvent = $this->queueHandler(Events\PtrCreated::class);

    $this->successItems('pkg.rdns::ptr.created', $items->each($createEvent));
  }

  protected function updateAll(Collection $items) {
    $this->setPtr($items);
  }

  private function setPtr(Collection $items) {
    if ($this->auth->is('client')) {
      foreach($items as $ptr){
        try {
          $valid = $this->ptrValidator->validate($ptr->ip, $this->input('ptr'));
        } catch (\RuntimeException $exc) {
          abort(409, sprintf('The DNS lookup for %s failed. Please try again in a few minutes.', e($this->input('ptr'))));
        }

        if(!$valid){
          abort(409, sprintf('Invalid PTR. Please ensure that %s has an A or AAAA DNS record to %s.', e($this->input('ptr')), e($ptr->ip)));
        }
      }
    }

    $inputs = [
      'ptr' => $this->input('ptr') ?: null,
    ];
    $updateEvent = $this->queueHandler(Events\PtrPtrUpdated::class);

    $this->successItems(
      'pkg.rdns::ptr.changed',
      $items
        ->filter($this->changed($inputs))
        ->reject([$this, 'isCreating'])
        ->each($updateEvent),
      ['field' => 'PTR Record']
    );
  }

  protected function beforeCreate(Collection $items) {
    $this->setIp($items);
  }

  private function setIp(Collection $items) {
    $inputs = [
      'ip' => ($ip = $this->input('ip')),
      'entity_id' => ($entityId = $this->ptr->getEntityId($ip)),
    ];

    if ($existing = $this->ptrs->byIp($ip)) {
      $items->each(function (Ptr $item) use ($existing) {
        // Switch from creating the PTR to updating it.
        $item->id = $existing->id;
        $item->exists = true;
      });
    }

    if ($this->auth->is('client') && !$entityId) {
      abort(403, 'You do not have access to that IP.');
    }

    if ($this->auth->is('client') && !$existing) {
      $this->checkIpv6Limit($ip, $entityId);
    }

    $this->successItems(
      'pkg.rdns::ptr.changed',
      $items->filter($this->changed($inputs))->reject([$this, 'isCreating']),
      ['field' => 'IP']
    );
  }

  /**
   * Clients may only create a limited number of IPv6 PTR records per IP
   * entity (setting pkg.rdns.ipv6.limit, default 20): IPv6 ranges are too
   * large to enumerate, so records are created ad-hoc and need a cap.
   * Admins are not limited. Updates to existing records are not counted.
   *
   * @param string $ip
   * @param int|null $entityId
   */
  private function checkIpv6Limit($ip, $entityId) {
    $binary = inet_pton($ip);
    if ($binary === false || strlen($binary) !== 16) {
      // Not an IPv6 address.
      return;
    }

    $settings = app('Settings');
    $value = $settings->{'pkg.rdns.ipv6.limit'} ?? null;
    $limit = ($value === null || $value === '') ? 20 : (int) $value;

    $count = $this->ptrs
      ->where('entity_id', $entityId)
      ->whereRaw('LENGTH(ip) = 16')
      ->count();

    if ($count >= $limit) {
      abort(422, sprintf(
        'The IPv6 rDNS limit of %d records for this IP range has been reached.',
        $limit
      ));
    }
  }
}
