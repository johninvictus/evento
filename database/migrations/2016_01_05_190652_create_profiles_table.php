<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('profiles', function (Blueprint $table) {

            $table->increments('id');
            $table->Integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');
            $table->string('gender');
            $table->string('website');
            $table->text('short_description');
            $table->text('telephone');
            $table->string('public_email');
            $table->string('profile_pic_min');
            $table->string('profile_pic_max');;
            $table->string('cover_image_min');
            $table->string('cover_image_max');
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
       Schema::drop('profiles');
    }
}
