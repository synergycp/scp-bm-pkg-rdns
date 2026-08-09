<?php

namespace Packages\Rdns\App\Ptr;

use Packages\Rdns\App\Ptr\DnsRecordService;

class PtrValidateRdns {

  /**
   * @var DnsRecordService
   */
  protected $dns;

  /**
   * @param DnsRecordService $dns
   */
  public function __construct(DnsRecordService $dns){
    $this->dns = $dns;
  }

  /**
   * @param string $ip
   * @param string $domain
   * 
   * @return bool
   */
  public function validate($ip, $domain): bool {
      $answers = $this->dns->getARecords($domain);
      foreach($answers as $record) {
          switch($record['type']) {
              case "A":
                  if ($this->ipsMatch($record['ip'] ?? '', $ip)) return true;
                  break;
              case "AAAA":
                if ($this->ipsMatch($record['ipv6'] ?? '', $ip)) return true;
          }
      }
      return false;
  }

  /**
   * Compare IPs in binary form so notation differences (IPv6 compression, case) don't cause false negatives.
   *
   * @param string $recordIp
   * @param string $ip
   *
   * @return bool
   */
  private function ipsMatch(string $recordIp, string $ip): bool {
      if (!filter_var($recordIp, FILTER_VALIDATE_IP) || !filter_var($ip, FILTER_VALIDATE_IP)) {
          return false;
      }

      return inet_pton($recordIp) === inet_pton($ip);
  }
}
