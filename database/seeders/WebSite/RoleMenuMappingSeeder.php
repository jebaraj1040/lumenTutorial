<?php

namespace Database\Seeders\WebSite;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RoleMenuMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [];
        $now = Carbon::now();

        for ($i = 1; $i <= 35; $i++) {
            $data[] = [
                'role_id' => 1,
                'menu_id' => $i,
                'is_active' => '1',
                'created_at' => $now,
                'created_by' => 1,
            ];
        }

        DB::table('role_menu_mapping')->insert($data);
    }
}
