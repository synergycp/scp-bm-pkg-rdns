<?php

namespace Packages\Rdns\App\Ptr;

use Illuminate\Support\Collection;
use App\Support\Http\DeleteService;
use App\Api\ApiAuthService;

/**
 * Delete Ptrs.
 */
class PtrDeleteService
extends DeleteService
{
    /**
     * @var ApiAuthService
     */
    protected $auth;

    /**
     * @param ApiAuthService $auth
     */
    public function boot(
        ApiAuthService $auth
    ) {
        $this->auth = $auth;
    }

    /**
     * @param Collection $items
     */
    protected function afterDelete(Collection $items)
    {
        $this->successItems('pkg.rdns::ptr.deleted', $items);
    }

    /**
     * @param Ptr $item
     */
    protected function delete($item)
    {
        $this->checkCanDelete($item);
        $item->delete();
        $this->queue(new Events\PtrDeleted($item));
    }

    /**
     * @param Ptr $item
     */
    protected function checkCanDelete(Ptr $item)
    {
        if ($this->auth->is('admin')) {
        }
    }
}
