<?php

namespace Database\Seeders\WebSite;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('role_master')->insert([
            'name' => 'Admin',
            'handle' => 'admin',
            'created_at' => Carbon::now(),
            'created_by' => 1,
        ]);
    }
}
