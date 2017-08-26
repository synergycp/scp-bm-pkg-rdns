<?php

namespace Packages\Rdns\App\Ptr;

use Illuminate\Database\Eloquent\Builder;
use App\Support\Http\FilterService;

/**
 * Filter Ptrs by those visible to the specific Request.
 */
class PtrFilterService
extends FilterService
{
    /**
     * @var PtrListRequest
     */
    protected $request;
    protected $requestClass = PtrListRequest::class;

    /**
     * @param Builder $query
     */
    public function viewable(Builder $query)
    {
    }

    public function query(Builder $query)
    {
        $this->prepare()->apply($query);

        // Filter raw text search
        if ($searchText = $this->request->input('q')) {
            $query->search(
                $this->search->search($searchText)
            );
        }

        return $query;
    }
}
