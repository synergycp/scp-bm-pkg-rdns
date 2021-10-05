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
  private $rdns;

  /**
   * PtrUpdateService constructor.
   *
   * @param PtrService    $ptr
   * @param PtrRepository $ptrs
   * @param PtrValidateRdns $rdns
   */
  public function boot(PtrService $ptr, PtrRepository $ptrs, PtrValidateRdns $rdns) {
    $this->ptr = $ptr;
    $this->ptrs = $ptrs;
    $this->rdns = $rdns;
  }

  public function afterCreate(Collection $items) {
    $createEvent = $this->queueHandler(Events\PtrCreated::class);

    $this->successItems('pkg.rdns::ptr.created', $items->each($createEvent));
  }

  protected function updateAll(Collection $items) {
    $this->setPtr($items);
  }

  private function setPtr(Collection $items) {
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
    if(!$this->rdns->validate($this->input('ip'), $this->input('ptr'))){
      abort(409, "Invalid IP");
    }
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

    $this->successItems(
      'pkg.rdns::ptr.changed',
      $items->filter($this->changed($inputs))->reject([$this, 'isCreating']),
      ['field' => 'IP']
    );
  }
}
