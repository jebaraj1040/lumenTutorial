<?php

namespace Database\Seeders\HousingJourney;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HjMasterMappingCcStageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $mappingData = [
            ['master_cc_stage_id' => 1, 'master_cc_sub_stage_id' => 1],
            ['master_cc_stage_id' => 1, 'master_cc_sub_stage_id' => 2],
            ['master_cc_stage_id' => 1, 'master_cc_sub_stage_id' => 3],
            ['master_cc_stage_id' => 1, 'master_cc_sub_stage_id' => 4],
            ['master_cc_stage_id' => 2, 'master_cc_sub_stage_id' => 5],
            ['master_cc_stage_id' => 3, 'master_cc_sub_stage_id' => 6],
            ['master_cc_stage_id' => 4, 'master_cc_sub_stage_id' => 7],
            ['master_cc_stage_id' => 4, 'master_cc_sub_stage_id' => 8],
            ['master_cc_stage_id' => 5, 'master_cc_sub_stage_id' => 9],
            ['master_cc_stage_id' => 6, 'master_cc_sub_stage_id' => 10],
            ['master_cc_stage_id' => 7, 'master_cc_sub_stage_id' => 11],
            ['master_cc_stage_id' => 7, 'master_cc_sub_stage_id' => 12],
            ['master_cc_stage_id' => 8, 'master_cc_sub_stage_id' => 13],
            ['master_cc_stage_id' => 9, 'master_cc_sub_stage_id' => 14],
            ['master_cc_stage_id' => 10, 'master_cc_sub_stage_id' => 15],
            ['master_cc_stage_id' => 10, 'master_cc_sub_stage_id' => 16],
            ['master_cc_stage_id' => 11, 'master_cc_sub_stage_id' => 17],
            ['master_cc_stage_id' => 11, 'master_cc_sub_stage_id' => 18],
            ['master_cc_stage_id' => 11, 'master_cc_sub_stage_id' => 19],
            ['master_cc_stage_id' => 11, 'master_cc_sub_stage_id' => 20],
            ['master_cc_stage_id' => 12, 'master_cc_sub_stage_id' => 21],
            ['master_cc_stage_id' => 13, 'master_cc_sub_stage_id' => 22],
            ['master_cc_stage_id' => 13, 'master_cc_sub_stage_id' => 23],
            ['master_cc_stage_id' => 14, 'master_cc_sub_stage_id' => 24],
            ['master_cc_stage_id' => 14, 'master_cc_sub_stage_id' => 25],
            ['master_cc_stage_id' => 14, 'master_cc_sub_stage_id' => 26],
            ['master_cc_stage_id' => 14, 'master_cc_sub_stage_id' => 27],
            ['master_cc_stage_id' => 14, 'master_cc_sub_stage_id' => 28],
        ];

        foreach ($mappingData as $key => &$subStage) {
            $key++;
            $count =  DB::table('hj_mapping_cc_stage')->where('id', $key)->count();
            if ($count) {
                DB::table('hj_mapping_cc_stage')->where('id', $key)->update($subStage);
            } else {
                DB::table('hj_mapping_cc_stage')->insert($subStage);
            }
        }
    }
}
