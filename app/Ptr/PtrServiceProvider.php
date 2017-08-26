<?php

namespace Packages\Rdns\App\Ptr;

use App\Support\ClassMap;
use App\Support\ServiceProvider;

/**
 * Provide the Ptr feature to the Application.
 */
class PtrServiceProvider
extends ServiceProvider
{
    /**
     * @var array
     */
    protected $providers = [
        PtrEventServiceProvider::class,
        PtrRoutesProvider::class,
    ];

    /**
     * @param ClassMap $classMap
     */
    public function boot(ClassMap $classMap)
    {
        $classMap
            ->map('pkg.rdns.ptr', Ptr::class)
        ;
    }
}
