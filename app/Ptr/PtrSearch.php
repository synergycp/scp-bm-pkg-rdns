<?php

namespace Packages\Rdns\App\Ptr;

use App\Database\Models\Traits\Searchable;

trait PtrSearch
{
    use Searchable;

    /**
     * @var array
     */
    protected $searchCols = [
        'ptr', 'ip',
    ];
}
