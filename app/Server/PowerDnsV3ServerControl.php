<?php

namespace Packages\Rdns\App\Server;

use App\Ip\IpAddressContract;
use App\Ip\IpService;
use function array_shift;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Foundation\Application;
use Packages\Rdns\App\Util\ZoneUtils;

class PowerDnsV3ServerControl
    implements IServerControl
{
    const PTR_TYPE = 'PTR';

    const SOA_TYPE = 'SOA';

    const SOA_CONFIG = [
        // TODO: Comments on what these values mean
        0,
        10800,
        3600,
        604800,
        3600,
    ];

    const ACTION_DELETE = 'DELETE';

    const ACTION_REPLACE = 'REPLACE';

    const TTL = 3600;

    /**
     * @var Client
     */
    protected $http;

    /**
     * @var ZoneUtils
     */
    protected $zoneUtils;

    /**
     * @var IpService
     */
    protected $ip;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $key;

    /**
     * @var array<string>
     */
    private $nameServers;

    /**
     * PowerDnsV3ServerControl constructor.
     *
     * @param Client      $http
     * @param ZoneUtils   $zoneUtils
     * @param IpService   $ip
     * @param Application $app
     * @param string      $host
     * @param string      $key
     * @param array       $nameServers
     */
    public function __construct(
        Client $http,
        ZoneUtils $zoneUtils,
        IpService $ip,
        Application $app,
        $host,
        $key,
        array $nameServers
    ) {
        $this->http = $http;
        $this->zoneUtils = $zoneUtils;
        $this->ip = $ip;
        $this->app = $app;
        $this->host = $host;
        $this->key = $key;
        $this->nameServers = $nameServers;
    }

    /**
     * @param $ip
     * @param $ptr
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function createPtr($ip, $ptr)
    {
        return $this->generatePtrUpdateRequest($ip, self::ACTION_REPLACE, $ptr);
    }

    private function generatePtrUpdateRequest($ip, $action, $ptr = '')
    {
        $ip = $this->ip->make($ip);
        $zone = $this->createZone($ip);
        $name = $this->zoneUtils->getPtrNameFromIP($ip);

        return $this->request('PATCH', 'servers/localhost/zones/' . $zone, [
            'rrsets' => [[
                'name' => $name,
                'type' => self::PTR_TYPE,
                'changetype' => $action,
                'records' => [
                    $this->generatePtrRecord($name, $ptr),
                ],
                'comments' => [[
                    'account' => 'SynergyCP',
                    'content' => 'Created by SynergyCP',
                ]],
            ]],
        ]);
    }

    /**
     * @param IpAddressContract $ip
     *
     * @return string
     */
    private function createZone(IpAddressContract $ip)
    {
        $name = $this->zoneUtils->getZoneNameFromIP($ip);

        // Get every name server except for the master one.
        $nameServers = $this->nameServers;
        array_shift($nameServers);

        try {
            $this->request('POST', 'servers/localhost/zones', [
                'name' => $name,
                'kind' => 'Native',
                'nameservers' => $nameServers,
                'records' => [
                    $this->generateSOARecord($name),
                ],
            ]);
        } catch (ClientException $exc) {
            if ($exc->getCode() === 422) {
                // Duplicate error, so just ignore it.
                // TODO: Not always duplicate error!
                return $name;
            }

            throw $exc;
        }

        return $name;
    }

    /**
     * @param string     $method
     * @param string     $uri
     * @param array|null $data
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function request($method, $uri, array $data = null)
    {
        return $this->http->request($method, sprintf(
            'http://%s/%s',
            $this->host,
            $uri
        ), [
            'json' => $data,
            'headers' => [
                'X-API-Key' => $this->key,
            ],
        ]);
    }

    /**
     * @param string $zone
     *
     * @return array
     */
    private function generateSOARecord($zone)
    {
        return [
            'type' => self::SOA_TYPE,
            'ttl' => self::TTL,
            'name' => $zone,
            'content' => implode(
                ' ',
                array_merge(array_slice($this->nameServers, 0, 2), self::SOA_CONFIG)
            ),
            'priority' => 1,
            'disabled' => false,
        ];
    }

    private function generatePtrRecord($name, $ptr)
    {
        return [
            'content' => $ptr,
            'name' => $name,
            'ttl' => self::TTL,
            'type' => self::PTR_TYPE,
            'priority' => 1,
            'disabled' => false,
        ];
    }

    public function deletePtr($ip)
    {
        return $this->generatePtrUpdateRequest($ip, self::ACTION_DELETE);
    }
}
