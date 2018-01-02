<?php

namespace Packages\Rdns\App\Server;


interface IServerControl
{
    /**
     * @param string $ip
     * @param string $ptr
     */
    public function createPtr($ip, $ptr);

    /**
     * @param string $ip
     */
    public function deletePtr($ip);
}
