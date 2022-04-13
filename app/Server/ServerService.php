<?php

namespace Packages\Rdns\App\Server;

use Illuminate\Foundation\Application;

class ServerService
{
    /**
     * @var Application
     */
    private $app;

    private $map = [
        'PowerDNS v4' => PowerDnsV4ServerControl::class,
        'PowerDNS v3' => PowerDnsV3ServerControl::class,
        'SynergyCP API' => SynergyServerControl::class,
    ];

    /**
     * ServerService constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @return IServerControl
     */
    public function get()
    {
        $settings = $this->app->make('Settings');
        $class = array_get(
            $this->map,
            $settings->{'pkg.rdns.api.type'},
            SynergyServerControl::class
        );
        $parameters = [
            'host' => $settings->{'pkg.rdns.api.host'},
            'key' => $settings->{'pkg.rdns.api.key'},
            'nameServers' => $this->getNameServers($settings),
        ];
        $version = $this->app->version();
        
        if($version >= '5.4.36') {
            return $this->app->makeWith($class, $parameters);
        } else {
            return $this->app->make($class, $parameters);
        }
    }

    /**
     * @param \stdClass $settings
     *
     * @return array<string>
     */
    private function getNameServers($settings)
    {
        $nameserversCSV = $settings->{'pkg.rdns.nameservers'};

        return array_filter(
            array_map(function ($domain) {
                return trim($domain);
            }, explode(',', $nameserversCSV))
        );
    }
}
