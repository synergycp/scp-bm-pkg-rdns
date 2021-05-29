<?php

namespace Packages\Rdns\App\Ptr;

use App\Api;
use Illuminate\Database\Eloquent\Collection;

/**
 * Handle HTTP requests regarding Ptrs.
 */
class PtrController extends Api\Controller {
  use Api\Traits\ListResource;
  use Api\Traits\ShowResource;
  use Api\Traits\UpdateResource;
  use Api\Traits\DeleteResource;

  /**
   * @var PtrRepository
   */
  protected $items;

  /**
   * @var PtrFilterService
   */
  protected $filter;

  /**
   * @var PtrUpdateService
   */
  protected $update;

  /**
   * @var PtrDeleteService
   */
  protected $delete;

  /**
   * @var PtrTransformer
   */
  protected $transform;

  /**
   * @var PtrFormRequest
   */
  protected $request;

  /**
   * @param PtrRepository $items
   * @param PtrFilterService $filter
   * @param PtrUpdateService $update
   * @param PtrDeleteService $delete
   * @param PtrTransformer $transform
   * @param PtrFormRequest $request
   */
  public function boot(
    PtrRepository $items,
    PtrFilterService $filter,
    PtrUpdateService $update,
    PtrDeleteService $delete,
    PtrTransformer $transform,
    PtrFormRequest $request
  ) {
    $this->items = $items;
    $this->filter = $filter;
    $this->update = $update;
    $this->delete = $delete;
    $this->transform = $transform;
    $this->request = $request;
  }

  /**
   * Filter the Repository by viewable entries.
   */
  public function filter() {
    return $this->filter->viewable($this->items->query());
  }

  public function store() {
    if ($id = $this->checkIp()) {
      return $this->update($id);
    }

    call_user_func_array(array($this, 'filter'), func_get_args());
    $items = new Collection(array($this->items->make()));
    try {
      $create = $this->update->create($items);
    } catch (\Exception $error) {
      return $this->handleError($error);
    }
    $message = $create->hasError()
      ? null
      : $this->transform->resource($items->first());
    return response()->messages($create->all(), $message);
  }

  private function checkIp() {
    if (!$this->request->has('ip')) {
      return false;
    }

    $ip = $this->request->get('ip');
    $ptr = $this->items->where('ip', $ip)->first();
    if (!$ptr) {
      return false;
    }

    return $ptr->getKey();
  }
}
