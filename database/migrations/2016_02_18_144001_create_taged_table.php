<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateTagedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    /*
     * This table will help link tags
     * ***/
    public function up()
    {
        Schema::create('taged',function(Blueprint $table){
            $table->increments('id');
            $table->integer('tag_id')->unsigned();
            $table->integer('event_id')->unsigned();

            $table->foreign('tag_id')->references('id')->on('tags')->cascade();
            $table->foreign('event_id')->references('id')->on('events')->cascade();

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
        Schema::drop('taged');
    }
}
