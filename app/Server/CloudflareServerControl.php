<?php

namespace Packages\Rdns\App\Server;

use App\Ip\IpService;
use GuzzleHttp\Client;
use Packages\Rdns\App\Util\ZoneUtils;

class CloudflareServerControl implements IServerControl {
  const API_BASE = 'https://api.cloudflare.com/client/v4';

  const PTR_TYPE = 'PTR';

  const TTL = 3600;

  /**
   * @var Client
   */
  private $http;

  /**
   * @var ZoneUtils
   */
  private $zoneUtils;

  /**
   * @var IpService
   */
  private $ip;

  /**
   * @var string
   */
  private $key;

  /**
   * @var array
   */
  private $zoneIdCache = [];

  /**
   * @var string|null
   */
  private $createdZoneInfo = null;

  /**
   * @param Client     $http
   * @param ZoneUtils  $zoneUtils
   * @param IpService  $ip
   * @param string     $host
   * @param string     $key
   * @param array      $nameServers
   */
  public function __construct(
    Client $http,
    ZoneUtils $zoneUtils,
    IpService $ip,
    $host,
    $key,
    array $nameServers
  ) {
    $this->http = $http;
    $this->zoneUtils = $zoneUtils;
    $this->ip = $ip;
    $this->key = $key;
  }

  /**
   * @param string $ip
   * @param string $ptr
   */
  public function createPtr($ip, $ptr) {
    $this->createdZoneInfo = null;

    $ip = $this->ip->make($ip);
    $zoneName = $this->zoneUtils->getZoneNameFromIP($ip);
    $zoneId = $this->getZoneId($zoneName);
    $ptrName = $this->zoneUtils->getPtrNameFromIP($ip);
    $content = $this->zoneUtils->getCanonicalName($ptr);

    $existingRecordId = $this->findExistingRecord($zoneId, $ptrName);

    $data = [
      'type' => self::PTR_TYPE,
      'name' => $ptrName,
      'content' => $content,
      'ttl' => self::TTL,
    ];

    if ($existingRecordId) {
      $this->request('PUT', "zones/{$zoneId}/dns_records/{$existingRecordId}", $data);
    } else {
      $this->request('POST', "zones/{$zoneId}/dns_records", $data);
    }

    return $this->createdZoneInfo;
  }

  /**
   * @param string $ip
   */
  public function deletePtr($ip) {
    $ip = $this->ip->make($ip);
    $zoneName = $this->zoneUtils->getZoneNameFromIP($ip);
    $zoneId = $this->getZoneId($zoneName);
    $ptrName = $this->zoneUtils->getPtrNameFromIP($ip);

    $existingRecordId = $this->findExistingRecord($zoneId, $ptrName);

    if (!$existingRecordId) {
      return null;
    }

    return $this->request('DELETE', "zones/{$zoneId}/dns_records/{$existingRecordId}");
  }

  /**
   * @param string $zoneName
   *
   * @return string
   */
  private function getZoneId($zoneName) {
    if (isset($this->zoneIdCache[$zoneName])) {
      return $this->zoneIdCache[$zoneName];
    }

    $response = $this->request('GET', 'zones', ['name' => $zoneName], true);
    $body = json_decode($response->getBody()->getContents(), true);

    if (empty($body['result'])) {
      return $this->createZone($zoneName);
    }

    $this->zoneIdCache[$zoneName] = $body['result'][0]['id'];

    return $this->zoneIdCache[$zoneName];
  }

  /**
   * @param string $zoneName
   *
   * @return string
   */
  private function createZone($zoneName) {
    $accountId = $this->getAccountId();

    $response = $this->request('POST', 'zones', [
      'name' => $zoneName,
      'account' => ['id' => $accountId],
    ]);

    $body = json_decode($response->getBody()->getContents(), true);
    $zoneId = $body['result']['id'];
    $nameServers = $body['result']['name_servers'];

    $nsList = implode(', ', $nameServers);
    $this->createdZoneInfo = "Zone '{$zoneName}' created in Cloudflare. Assign these nameservers: {$nsList}";

    $this->zoneIdCache[$zoneName] = $zoneId;

    return $zoneId;
  }

  /**
   * @return string
   */
  private function getAccountId() {
    $response = $this->request('GET', 'accounts', null, true);
    $body = json_decode($response->getBody()->getContents(), true);

    if (empty($body['result'])) {
      throw new \RuntimeException(
        'Cloudflare: no accounts found for the provided API token.'
      );
    }

    return $body['result'][0]['id'];
  }

  /**
   * @param string $zoneId
   * @param string $ptrName
   *
   * @return string|null
   */
  private function findExistingRecord($zoneId, $ptrName) {
    $response = $this->request('GET', "zones/{$zoneId}/dns_records", [
      'type' => self::PTR_TYPE,
      'name' => $ptrName,
    ], true);

    $body = json_decode($response->getBody()->getContents(), true);

    if (empty($body['result'])) {
      return null;
    }

    return $body['result'][0]['id'];
  }

  /**
   * @param string     $method
   * @param string     $uri
   * @param array|null $data
   * @param bool       $asQuery
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  private function request($method, $uri, array $data = null, $asQuery = false) {
    $options = [
      'headers' => [
        'Authorization' => 'Bearer ' . $this->key,
      ],
    ];

    if ($data) {
      if ($asQuery) {
        $options['query'] = $data;
      } else {
        $options['json'] = $data;
      }
    }

    return $this->http->request(
      $method,
      self::API_BASE . '/' . $uri,
      $options
    );
  }
}
