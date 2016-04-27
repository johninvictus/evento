<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(Oauth_clientsSeeder::class);
        $this->call(UsersSeeder::class);
        $this->call(CountriesTableSeeder::class);
    }
}
