<?php

namespace Database\Seeders\WebSite;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RoleUserMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('role_user_mapping')->insert(
            [
                [
                    'role_id' => 1,
                    'user_id' => 1,
                    'created_at' => Carbon::now(),
                    'created_by' => 1,
                ]
            ]
        );
    }
}
