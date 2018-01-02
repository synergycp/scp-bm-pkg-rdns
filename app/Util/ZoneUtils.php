<?php

namespace Packages\Rdns\App\Util;

class ZoneUtils
{
    /**
     * @param string $ip
     *
     * @return string
     */
    public function getNameFromIP($ip)
    {
        $octets = explode('.', $ip);
        array_pop($octets);
        $octets = array_reverse($octets);

        return implode('.', $octets) . '.in-addr.arpa';
    }
}
