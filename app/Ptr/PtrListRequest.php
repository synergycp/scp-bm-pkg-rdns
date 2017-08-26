<?php

namespace Packages\Rdns\App\Ptr;

use App\Http\Requests\ListRequest;

class PtrListRequest
extends ListRequest
{
    public $orders = [
        'ip' => 'ip',
        'ptr' => 'ptr',
        'entity_id' => 'entity_id',
    ];
}
