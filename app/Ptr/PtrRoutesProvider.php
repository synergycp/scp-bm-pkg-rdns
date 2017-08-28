<?php

namespace Packages\Rdns\App\Ptr;

use App\Http\RouteServiceProvider;
use Illuminate\Routing\Router;

/**
 * Routes regarding Ptr.
 */
class PtrRoutesProvider
    extends RouteServiceProvider
{
    /**
     * @var string
     */
    protected $package = 'rdns';

    /**
     * Setup Routes.
     */
    public function bootRoutes()
    {
        $base = implode('.', ['pkg', $this->package, '']);
        $this->sso->map(Report::class, $base . 'ptr');

        $this->loadTranslationsFrom(
            $this->basePath() . '/resources/lang',
            'pkg.' . $this->folder()
        );
    }

    /**
     * @return string
     */
    protected function basePath()
    {
        return sprintf(
            '%s/packages/%s',
            $this->app->basePath(),
            $this->folder()
        );
    }

    /**
     * @return string
     */
    protected function folder()
    {
        return $this->package;
    }

    /**
     * @param Router $router
     */
    protected function api(Router $router)
    {
        $router->resource(
            'ptr',
            PtrController::class
        );

        $router->post(
            'ptr/zone',
            Zone\ZoneController::class . '@store'
        );
    }
}
