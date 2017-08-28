<?php

namespace Packages\Rdns\App\Server;

use GuzzleHttp\Client;
use Illuminate\Foundation\Application;

class ServerService
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var Client
     */
    private $http;

    /**
     * ServerService constructor.
     *
     * @param Application $app
     * @param Client      $http
     */
    public function __construct(Application $app, Client $http)
    {
        $this->app = $app;
        $this->http = $http;
    }

    /**
     * @return ServerControl
     */
    public function get()
    {
        $settings = $this->app->Settings;

        return new ServerControl(
            $this->http,
            $settings->{'pkg.rdns.api.host'},
            $settings->{'pkg.rdns.api.key'}
        );
    }
}
