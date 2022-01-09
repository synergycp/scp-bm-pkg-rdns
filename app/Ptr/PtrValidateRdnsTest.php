<?php

namespace Packages\Rdns\App\Ptr;

use Packages\Rdns\App\RdnsTestCase;
use Packages\Rdns\App\Ptr\PtrValidateRdns;
use Packages\Rdns\App\Ptr\DnsRecordService;
use Mockery;

class PtrValidateRdnsTest extends RdnsTestCase {
    
  public function setUp() {
      parent::setUp();
      $this->dns = Mockery::mock(DnsRecordService::class);
  }
  /**
   * @return PtrValidateRdns
   */
  private function rdns() {
    return new PtrValidateRdns($this->dns);
  }

  public function testGivenValidIPItShouldReturnTrue(){
      $ip = '1.1.1.1';
      $host = 'test-ptr';
      $this->mockDns($host, "ip", $ip, "A");
      $result = $this->rDns()->validate($ip, $host);
      $this->assertEquals($result, true);
  }

  public function testGivenValidIPV6ItShouldReturnTrue(){
      $ip = "fe80:1:2:3:a:bad:1dea:dad";
      $host = 'test-ptr';
      $this->mockDns($host, "ipv6", $ip, "AAAA");
      $result = $this->rDns()->validate($ip, $host);
      $this->assertEquals($result, true);
  }

  public function testGivenInvalidIPItShouldReturnFalse(){
      $ip = '9.9.9.9';
      $host = 'test-ptr';
      $this->mockDns($host, "ip", '1.1.1.1', "A");
      $result = $this->rDns()->validate($ip, $host);
      $this->assertEquals($result, false);
  }

  public function testGivenInvalidIPV6ItShouldReturnFalse(){
      $ip = "fe80:111:222:3:a:badsd:1dea:dad";
      $host = 'test-ptr';
      $this->mockDns($host, "ipv6", "fe80:1:2:3:a:bad:1dea:dad", "AAAA");
      $result = $this->rDns()->validate($ip, $host);
      $this->assertEquals($result, false);
  }

  private function mockDns($host, $ipType, $ip, $type) {
    $this->dns
      ->shouldReceive('getARecords')
      ->with($host)
      ->times()
      ->andReturn(
        array(
          array("host"=> $host, $ipType => $ip, "type" => $type)
        )
      );
  }
}