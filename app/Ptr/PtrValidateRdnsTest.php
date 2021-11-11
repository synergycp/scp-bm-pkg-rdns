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
        $this->dns
          ->shouldReceive('get')
          ->andReturn(
            array(
              array("host"=> "test-ptr","ip"=> '1.1.1.1', "type" => "A")              
            )
          );
        $result = $this->rDns()->validate($ip, 'test-ptr');
        $this->assertEquals($result, true);
    }

    public function testGivenValidIPV6ItShouldReturnTrue(){
        $ip = "fe80:1:2:3:a:bad:1dea:dad";
        $this->dns
          ->shouldReceive('get')
          ->andReturn(
            array(
              array("host"=> "test-ptr","ipv6"=> 'fe80:1:2:3:a:bad:1dea:dad', "type" => "AAAA")
            )
          );
        $result = $this->rDns()->validate($ip, 'test-ptr');
        $this->assertEquals($result, true);
    }

    public function testGivenInvalidIPItShouldReturnFalse(){
        $ip = '9.9.9.9';
        $this->dns
          ->shouldReceive('get')
          ->andReturn(
            array(
              array("host"=> "test-ptr","ip"=> '1.1.1.1', "type" => "A")
            )
          );
        $result = $this->rDns()->validate($ip, 'test-ptr');
        $this->assertEquals($result, false);
    }
}