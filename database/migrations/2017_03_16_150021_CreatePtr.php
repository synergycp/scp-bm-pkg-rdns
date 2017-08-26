<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Support\Database\Migration;

class CreatePtr extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema()->create('pkg_rdns_ptrs', function (Blueprint $table) {
            $table->increments('id');

            $table->ip('ip');
            $table->string('ptr', 200);

            $table->integer('entity_id')->unsigned()->nullable();
            $table->foreign('entity_id')->references('id')->on('entities');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('pkg_rdns_ptrs');
    }
}
