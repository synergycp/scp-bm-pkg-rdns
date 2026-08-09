<?php

namespace Packages\Rdns\App\Server;

use GuzzleHttp\Client;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;

class ServerService
{
    /**
     * Guzzle's default timeout is infinite; bound requests so a hung DNS
     * provider cannot stall queue workers indefinitely.
     */
    const CONNECT_TIMEOUT = 10;

    const REQUEST_TIMEOUT = 30;

    /**
     * @var Application
     */
    private $app;

    private $map = [
        'PowerDNS v4' => PowerDnsV4ServerControl::class,
        'PowerDNS v3' => PowerDnsV3ServerControl::class,
        'SynergyCP API' => SynergyServerControl::class,
        'Cloudflare' => CloudflareServerControl::class,
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
        $type = $settings->{'pkg.rdns.api.type'} ?? null;
        // Note: Arr::get() returns the whole array for a null key, so only
        // look up the map when a type is actually set.
        $class = $type !== null ? Arr::get($this->map, $type) : null;

        if (!$class) {
            // Only fall back to the default provider when no type is set;
            // an unrecognized value must not silently route PTRs elsewhere.
            if ($type) {
                throw new \RuntimeException(
                    "Unknown rDNS provider type configured: {$type}"
                );
            }

            $class = SynergyServerControl::class;
        }

        $parameters = [
            'http' => new Client([
                'connect_timeout' => self::CONNECT_TIMEOUT,
                'timeout' => self::REQUEST_TIMEOUT,
            ]),
            'host' => $settings->{'pkg.rdns.api.host'} ?? null,
            'key' => $settings->{'pkg.rdns.api.key'} ?? null,
            'nameServers' => $this->getNameServers($settings),
        ];
        return $this->app->makeWith($class, $parameters);
    }

    /**
     * @param \stdClass $settings
     *
     * @return array<string>
     */
    private function getNameServers($settings)
    {
        $nameserversCSV = $settings->{'pkg.rdns.nameservers'} ?? '';

        return array_filter(
            array_map(function ($domain) {
                return trim($domain);
            }, explode(',', $nameserversCSV))
        );
    }
}
