<?php

namespace Database\Seeders\HousingJourney;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HjMappingProductStepCcStageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $stages = [
            [
                'master_product_step_id' => 1,
                'master_cc_stage_id' => 1,
            ],
            [
                'master_product_step_id' => 2,
                'master_cc_stage_id' => 2,
            ],
            [
                'master_product_step_id' => 3,
                'master_cc_stage_id' => 3,
            ],
            [
                'master_product_step_id' => 4,
                'master_cc_stage_id' => 4,
            ],
            [
                'master_product_step_id' => 5,
                'master_cc_stage_id' => 5,
            ],
            [
                'master_product_step_id' => 6,
                'master_cc_stage_id' => 6,
            ],
            [
                'master_product_step_id' => 7,
                'master_cc_stage_id' => 7,
            ],
            [
                'master_product_step_id' => 8,
                'master_cc_stage_id' => 8,
            ],
            [
                'master_product_step_id' => 9,
                'master_cc_stage_id' => 9,
            ],
            [
                'master_product_step_id' => 10,
                'master_cc_stage_id' => 10,
            ],
            [
                'master_product_step_id' => 11,
                'master_cc_stage_id' => 11,
            ],
            [
                'master_product_step_id' => 14,
                'master_cc_stage_id' => 11,
            ],
            [
                'master_product_step_id' => 12,
                'master_cc_stage_id' => 12,
            ],
            [
                'master_product_step_id' => 13,
                'master_cc_stage_id' => 13,
            ],
            [
                'master_product_step_id' => 15,
                'master_cc_stage_id' => 14,
            ],
        ];

        foreach ($stages as $key => $stage) {
            $key++;
            $count =  DB::table('hj_mapping_product_step_cc_stage')->where('id', $key)->count();
            if ($count) {
                DB::table('hj_mapping_product_step_cc_stage')->where('id', $key)->update($stage);
            } else {
                DB::table('hj_mapping_product_step_cc_stage')->insert($stage);
            }
        }
    }
}
