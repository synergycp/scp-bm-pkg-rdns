<?php

namespace Packages\Rdns\App\Ptr;

class DnsRecordService {
  
  /**
   * @param string $domain
   * 
   * @return array 
   */
  public function get($domain){
    return dns_get_record($domain, DNS_A + DNS_AAAA);    
  }
}