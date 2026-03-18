<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Support\Database\Migration;

class AddCascadeDeleteToPtr extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema()->table('pkg_rdns_ptrs', function (Blueprint $table) {
            $table->dropForeign(['entity_id']);
            $table->foreign('entity_id')
                ->references('id')
                ->on('entities')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema()->table('pkg_rdns_ptrs', function (Blueprint $table) {
            $table->dropForeign(['entity_id']);
            $table->foreign('entity_id')
                ->references('id')
                ->on('entities');
        });
    }
}
