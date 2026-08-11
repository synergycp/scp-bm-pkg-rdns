<?php

use App\Support\Database\Migration;
use App\Setting\Setting;

class AddIpv6RdnsLimitSetting extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $group = $this->addSettingGroup('DNS');

        $this->addSetting($group, Setting::TYPE_TEXT, 'pkg.rdns.ipv6.limit', [
            'value' => '20',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->deleteSetting('pkg.rdns.ipv6.limit');
    }
}
