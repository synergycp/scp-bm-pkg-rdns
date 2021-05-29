<?php

namespace Packages\Rdns\App\Util;

use App\Ip\IpAddressV4;
use App\Ip\IpAddressV6;
use Packages\Rdns\App\RdnsTestCase;

class ZoneUtilsTest extends RdnsTestCase {
  /**
   * @var ZoneUtils
   */
  private $zone;

  public function setUp() {
    $this->zone = new ZoneUtils();
    parent::setUp();
  }

  public function testGetZoneNameFromIPv4() {
    $this->assertEquals(
      '33.222.111.in-addr.arpa',
      $this->zone->getZoneNameFromIP(new IpAddressV4('111.222.33.4'))
    );
  }

  public function testGetZoneNameFromIPv6() {
    $this->assertEquals(
      '0.5.0.7.0.0.2.0.0.c.c.3.1.0.4.2.ip6.arpa',
      $this->zone->getZoneNameFromIP(new IpAddressV6('2401:3cc0:200:7050::'))
    );
  }

  public function testGetPtrNameFromIPv4() {
    $this->assertEquals(
      '4.33.222.111.in-addr.arpa',
      $this->zone->getPtrNameFromIP(new IpAddressV4('111.222.33.4'))
    );
  }

  public function testGetPtrNameFromIPv6() {
    $this->assertEquals(
      '2401:3cc0:0840:0000:0000:0000:0000:0000',
      (new IpAddressV6('2401:3cc0:840::'))->longName()
    );
    $this->assertEquals(
      '0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.4.8.0.0.c.c.3.1.0.4.2.ip6.arpa',
      $this->zone->getPtrNameFromIP(new IpAddressV6('2401:3cc0:840::'))
    );
  }
}
