<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        $this->call([
            // Website
            WebSite\MenuMasterSeeder::class,
            WebSite\UserSeeder::class,
            WebSite\RoleSeeder::class,
            WebSite\RoleMenuMappingSeeder::class,
            WebSite\RoleUserMappingSeeder::class,
            WebSite\UserMenuMappingSeeder::class,

            //Housing Journey
            HousingJourney\HjMasterProductStepSeeder::class,
            HousingJourney\HjMasterRelationshipSeeder::class,
            HousingJourney\HjMasterCcStageSeeder::class,
            HousingJourney\HjMasterCcSubStageSeeder::class,
            HousingJourney\HjMasterMappingCcStageSeeder::class,
            HousingJourney\HjMasterStateSeeder::class,
            HousingJourney\HjPaymentGatewaySeeder::class,
            HousingJourney\HjMappingProductStepCcStageSeeder::class,
            HousingJourney\HjBreVersionChangeSeeder::class
        ]);
    }
}
