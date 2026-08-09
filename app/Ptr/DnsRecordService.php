<?php

namespace Packages\Rdns\App\Ptr;

class DnsRecordService {
  
  /**
   * @param string $domain
   *
   * @return array
   *
   * @throws \RuntimeException when the DNS lookup itself fails (as opposed to returning no records)
   */
  public function getARecords ($domain): array {
    $records = dns_get_record($domain, DNS_A + DNS_AAAA);

    if ($records === false) {
      throw new \RuntimeException("DNS lookup failed for {$domain}");
    }

    return $records;
  }
}