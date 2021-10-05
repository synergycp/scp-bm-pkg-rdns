<?php

namespace Packages\Rdns\App\Ptr;


class PtrValidateRdns {

    /**
     * @return bool
     */
    public function validate($input, $domain) {
        $answers = dns_get_record($domain, DNS_A + DNS_AAAA);

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