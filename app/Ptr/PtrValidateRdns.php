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
     * @return bool
     */
    public function validate($input, $domain) {
        $answers = $this->dns->get($domain);
        foreach($answers as $x) {
            if($x['type'] == "A") {
                $ip = $x['ip'];
                if($ip == $input){
                    return true;
                }
            }
            else if($x['type'] == "AAAA") {
                $ip = $x['ipv6'];
                if($ip == $input){
                    return true;
                }
            }
        }
        return false;
    }
}