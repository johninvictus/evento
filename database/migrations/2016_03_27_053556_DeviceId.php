<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class DeviceId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::create('deviceid',function(Blueprint $table){
           $table->increments('id');
           $table->integer('user_id')->unsigned();
           $table->foreign('user_id')->references('id')->on('users')->cascade();
           $table->string('gsm_key');
           $table->timestamps();
       });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('deviceid');
    }
}
