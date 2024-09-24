<?php

namespace Database\Seeders\WebSite;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserMenuMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $userId = 1;
        $isActive = '1';
        $createdAt = Carbon::now();
        $createdBy = 1;

        $menuMappings = [];

        // Define menu mappings
        for ($menuId = 1; $menuId <= 35; $menuId++) {
            $menuMappings[] = [
                'user_id' => $userId,
                'menu_id' => $menuId,
                'is_active' => $isActive,
                'created_at' => $createdAt,
                'created_by' => $createdBy,
            ];
        }

        // Insert menu mappings
        DB::table('user_menu_mapping')->insert($menuMappings);
    }
}
