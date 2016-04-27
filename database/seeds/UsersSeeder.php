<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'username' => 'john invictus',
            'email' => 'invictusnitude@gmail.com',
            'password' => Hash::make('johnmarangu24@'),
            'created_at' => Carbon::now()->now(),
            'updated_at' => Carbon::now()->now(),
        ]);
    }
}
