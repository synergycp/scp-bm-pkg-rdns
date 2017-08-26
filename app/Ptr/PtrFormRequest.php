<?php

namespace Packages\Rdns\App\Ptr;

use App\Http\Requests\RestRequest;

class PtrFormRequest
extends RestRequest
{
    /**
     * Load rules.
     */
    public function boot()
    {
        $this->rules = [
        ];
    }
}
