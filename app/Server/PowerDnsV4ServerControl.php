<?php

namespace Packages\Rdns\App\Server;

use App\Ip\IpAddressContract;
use App\Ip\IpService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Foundation\Application;
use Packages\Rdns\App\Util\ZoneUtils;
use Psr\Http\Message\ResponseInterface;

class PowerDnsV4ServerControl implements IServerControl {
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
   * @var string[]
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
   * @return ResponseInterface
   */
  public function createPtr($ip, $ptr) {
    return $this->generatePtrUpdateRequest($ip, self::ACTION_REPLACE, $ptr);
  }

  private function generatePtrUpdateRequest($ip, $action, $ptr = '') {
    $ip = $this->ip->make($ip);
    $zone = $this->createZone($ip);
    $name = $this->zoneUtils->getPtrNameFromIP($ip);
    
    return $this->request('PATCH', "{$this->buildZoneBaseURL()}/{$zone}", [
      'rrsets' => [
        [
          'name' => $this->zoneUtils->getCanonicalName($name),
          'type' => self::PTR_TYPE,
          'ttl' => self::TTL,
          'changetype' => $action,
          'records' => [$this->generatePtrRecord($name, $ptr)],
          'comments' => [
            ['account' => 'SynergyCP', 'content' => 'Created by SynergyCP'],
          ],
        ],
      ],
    ]);
  }

  private function buildZoneBaseURL(): string {
    return $this->buildServerBaseURL() . '/localhost/zones';
  }

  /**
   * @param IpAddressContract $ip
   *
   * @return string
   * @throws GuzzleException
   */
  private function createZone(IpAddressContract $ip) {
    $name = $this->zoneUtils->getZoneNameFromIP($ip);

    // Get every name server except for the master one.
    $nameServers = $this->nameServers;
    array_shift($nameServers);

    try {
      $this->attemptCreateZone($name, $nameServers);
    } catch (ClientException $exc) {
      if ($exc->getCode() === 404) {
        $this->createServer();
        $this->attemptCreateZone($name, $nameServers);
      } else {
        throw $exc;
      }
    }

    return $name;
  }

  private function attemptCreateZone(string $name, array $nameServers): void {
    try {
      $this->request('POST', $this->buildZoneBaseURL(), [
        'name' => $this->zoneUtils->getCanonicalName($name),
        'kind' => 'Native',
        'nameservers' => array_map(function ($nameserver) {
          return $this->zoneUtils->getCanonicalName($nameserver);
        }, $nameServers),
        'records' => [$this->generateSOARecord($name)],
      ]);
    } catch (ClientException $exc) {
      $body = $exc
        ->getResponse()
        ->getBody()
        ->getContents();
      
      // Ignore duplicate zone errors.
      if ($exc->getCode() === 409 && $body === "Conflict") {
        return;
      }
      
      throw $exc;
    }
  }

  private function createServer(): void {
    $this->request('POST', $this->buildServerBaseURL(), [
      'ID' => 'localhost',
    ]);
  }

  private function buildServerBaseURL(): string {
    return 'api/v1/servers';
  }

  /**
   * @param string     $method
   * @param string     $uri
   * @param array|null $data
   *
   * @return ResponseInterface
   * @throws GuzzleException
   */
  private function request($method, $uri, array $data = null) {
    print_r($data);
    return $this->http->request(
      $method,
      sprintf('http://%s/%s', $this->host, $uri),
      ['json' => $data, 'headers' => ['X-API-Key' => $this->key]]
    );
  }

  /**
   * @param string $zone
   *
   * @return array
   */
  private function generateSOARecord($zone) {
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

  private function generatePtrRecord($name, $ptr) {
    return [
      'content' => $this->zoneUtils->getCanonicalName($ptr),
      'name' => $name,
      'ttl' => self::TTL,
      'type' => self::PTR_TYPE,
      'priority' => 1,
      'disabled' => false,
    ];
  }

  public function deletePtr($ip) {
    return $this->generatePtrUpdateRequest($ip, self::ACTION_DELETE);
  }
}
