<?php

namespace Packages\Rdns\App\Ptr;

use App\Database\ModelRepository;

/**
 * Store Ptrs in and retrieve them from the database.
 */
class PtrRepository
extends ModelRepository
{
    protected $model = Ptr::class;
}
