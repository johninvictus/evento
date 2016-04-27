<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events',function(Blueprint $table){
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('event_title');
            $table->timestamp('event_date');
            $table->string('min_poster')->default('min_poster.png');
            $table->string('max_poster')->default('max_poster.png');
            $table->string('event_thumbnail')->default('thumb_nail.png');
            $table->text('event_description');
            $table->integer('event_state')->default(0); //0 free 1 payed
            $table->string('event_price');
            $table->integer('location_provided')->default(0); //0 not provided 1 provided
            $table->string('lat');
            $table->string('longt');
            $table->integer('tag_provided')->default(0);
            $table->string('currency');
            $table->timestamps();

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
       Schema::drop('events');
    }
}
