<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance',function(Blueprint $table){
            $table->increments('id');
            $table->integer('event_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->integer('going')->default('0');;
            $table->integer('maybe')->default('0');;
            $table->timestamps();

            $table->foreign('event_id')->references('id')->on('events')->cascade();
            $table->foreign('user_id')->references('id')->on('users')->cascade();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
       Schema::drop('attendance');
    }
}
