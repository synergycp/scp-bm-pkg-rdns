<?php

use App\Support\Database\Migration;
use App\Setting\Setting;

class AddCloudflareSetting extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $setting = Setting::query()->where('name', 'pkg.rdns.api.type')->first();
        $setting->options = 'SynergyCP API,PowerDNS v3,PowerDNS v4,Cloudflare';
        $setting->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $setting = Setting::query()->where('name', 'pkg.rdns.api.type')->first();
        $setting->options = 'SynergyCP API,PowerDNS v3,PowerDNS v4';
        $setting->save();
    }
}
