<?php

namespace Packages\Rdns\App\Util;

use App\Ip\IpAddressContract;
use App\Ip\IpAddressV4;
use App\Ip\IpAddressV6;
use App\Support\Collection;

class ZoneUtils
{
    /**
     * @param IpAddressContract $ip
     *
     * @return string
     */
    public function getPtrNameFromIP(IpAddressContract $ip)
    {
        return $this->compileAddress(
            $this->getParts($ip),
            $ip
        );
    }

    /**
     * @param Collection        $parts
     * @param IpAddressContract $ip
     *
     * @return string
     */
    private function compileAddress(Collection $parts, IpAddressContract $ip)
    {
        return sprintf(
            "%s.%s.arpa",
            $parts
                ->reverse()
                // Note: intentional period delimiter, since colons in IPv6 addresses need to be converted to periods for zone names.
                ->implode('.'),
            is_a($ip, IpAddressV6::class) ? 'ip6' : 'in-addr'
        );
    }

    /**
     * @param IpAddressContract $ip
     *
     * @return string
     */
    public function getZoneNameFromIP(IpAddressContract $ip)
    {
        $parts = $this->getParts($ip);

        if (is_a($ip, IpAddressV6::class)) {
            $parts = $parts->slice(0, $parts->count() / 2);
        }

        if (is_a($ip, IpAddressV4::class)) {
            $parts->pop();
        }

        return $this->compileAddress($parts, $ip);
    }

    public function getCanonicalName(string $name): string {
      return $name . '.';
    }

    private function getParts(IpAddressContract $ip)
    {
        if (is_a($ip, IpAddressV6::class)) {
            $long = str_replace($ip->delim(), '', $ip->longName());

            return collection(str_split($long));
        }

        return $ip->parts();
    }
}
