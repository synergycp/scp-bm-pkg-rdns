<?php

namespace Packages\Rdns\App\Ptr;

class DnsRecordService {
  
  /**
   * @param string $domain
   * 
   * @return array 
   */
  public function getARecords ($domain): array {
    return dns_get_record($domain, DNS_A + DNS_AAAA);    
  }
}