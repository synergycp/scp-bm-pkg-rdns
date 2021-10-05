<?php

namespace Packages\Rdns\App\Ptr;

use Packages\Rdns\App\RdnsTestCase;
use Packages\Rdns\App\Ptr\PtrValidateRdns;
use App\Ip\IpAddressRangeContract;
use App\Ip\IpAddressV4;

function dns_get_record($domain, $type) {
    $record = array(
        array("host"=> "test-ptr","ip"=> '1.1.1.1', "type" => DNS_A),
        array("host"=> "test-ptr","ip"=> '1.1.1.4', "type" => DNS_MX),
        array("host"=> "test-ptr","ip"=> '1.1.1.1', "type" => DNS_NS),
        array("host"=> "test-ptr","ipv6"=> "fe80:1:2:3:a:bad:1dea:dad",'type' => DNS_AAAA)
    );
    $array = array();
    foreach($record as $ip){
        if($ip["host"] == $domain){
            if($type == $ip["type"]+DNS_AAAA || $type == $ip["type"]+DNS_A){
                if($ip["type"] == DNS_A) {
                    $ip["type"] = "A";
                }
                else if($ip["type"] == DNS_AAAA) {
                    $ip["type"] = "AAAA";
                }
                array_push($array,$ip);
            }
        }
    }
    return $array;
}

class PtrValidateRdnsTest extends RdnsTestCase {

    /**
     * @var IpAddressRangeContract
     */
    private $ipRange;
    
    public function setUp() {
        parent::setUp();
        $this->ipRange = IpAddressV4::range('1.1.1.1', '1.1.1.4');
    }

    public function testGivenValidIPItShouldReturnTrue(){
        $ip = $this->ipRange->start();
        $rDns = app(PtrValidateRdns::class);
        $result = $rDns->validate($ip, 'test-ptr');
        $this->assertEquals($result, true);
    }

    public function testGivenValidIPV6ItShouldReturnTrue(){
        $ip = "fe80:1:2:3:a:bad:1dea:dad";
        $rDns = app(PtrValidateRdns::class);
        $result = $rDns->validate($ip, 'test-ptr');
        $this->assertEquals($result, true);
    }

    public function testGivenInvalidIPItShouldReturnFalse(){
        $ip = $this->ipRange->end();
        $rDns = app(PtrValidateRdns::class);
        $result = $rDns->validate($ip, 'test-ptr');
        $this->assertEquals($result, false);
    }
}