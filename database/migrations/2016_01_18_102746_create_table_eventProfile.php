<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateTableEventProfile extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('eventprofiles',function(Blueprint $table){
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->integer('country_id')->unsigned();

            $table->foreign('user_id')->references('id')->on('users')->cascade();
            $table->foreign('country_id')->references('id')->on('countries')->cascade();

            $table->string('lat');
            $table->string('longt');
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
      Schema::drop('eventprofiles');
    }
}
