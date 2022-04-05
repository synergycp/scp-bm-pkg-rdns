<?php

namespace Packages\Rdns\App\Server;

use GuzzleHttp\Client;

class SynergyServerControl implements IServerControl
{
    /**
     * @var Client
     */
    protected $http;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $key;

    /**
     * ServerControl constructor.
     *
     * @param Client $http
     * @param string $host
     * @param string $key
     */
    public function __construct(Client $http, $host, $key)
    {
        $this->http = $http;
        $this->host = $host;
        $this->key = $key;
    }

    /**
     * @param $ip
     * @param $ptr
     */
    public function createPtr($ip, $ptr)
    {
        $this->request('POST', 'ptr', [
            'ip' => $ip,
            'ptr' => $ptr,
        ]);
    }

    public function deletePtr($ip)
    {
        $this->request('DELETE', 'ptr/'.$ip);
    }

    /**
     * @param string     $method
     * @param string     $uri
     * @param array|null $data
     */
    private function request($method, $uri, array $data = null)
    {
        $this->http->request($method, sprintf(
            'http:/%s/%s?key=%s',
            $this->host,
            $uri,
            urlencode($this->key)
        ), [
            'json' => $data,
        ]);
    }
}
