<?php

namespace Database\Seeders\WebSite;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('user')->insert([
            'email' => 'admin@test.com',
            'user_name' => 'admin',
            'password' => Hash::make('Password@123'),
            'profile_path' => null,
            'first_name' => 'Admin',
            'middle_name' => null,
            'is_active' => '1',
            'last_name' => 'Test',
            'phone_number' => 9876543210,
            'created_at' => Carbon::now(),
            'created_by' => 1,
        ]);
    }
}
