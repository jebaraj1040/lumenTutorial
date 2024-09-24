<?php

namespace Database\Seeders\HousingJourney;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class HjBreVersionChangeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('hj_bre_version')->insert(
            [
                'version' => 'v1',
                'created_at' => '2024-02-14 10:10:00'
            ]
        );
    }
}
