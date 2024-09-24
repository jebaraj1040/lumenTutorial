<?php

namespace Database\Seeders\HousingJourney;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HjMasterCcStageSeeder extends Seeder
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
                'name' => 'OTP',
                'handle' => 'otp',
            ],
            [
                'name' => 'Personal Details - applicant',
                'handle' => 'personal-details-applicant',
            ],
            [
                'name' => 'Employment - applicant',
                'handle' => 'employment-applicant',
            ],
            [
                'name' => 'Eligibility - applicant',
                'handle' => 'eligibility-applicant',
            ],
            [
                'name' => 'Personal Details - Co-applicant',
                'handle' => 'personal-details-co-applicant',
            ],
            [
                'name' => 'Employment Details - Co-applicant',
                'handle' => 'co-applicant-employment-details',
            ],
            [
                'name' => 'Eligibility - Co-applicant',
                'handle' => 'eligibility-co-applicant',
            ],
            [
                'name' => 'Address details',
                'handle' => 'address-details',
            ],
            [
                'name' => 'Property Details',
                'handle' => 'property-details',
            ],
            [
                'name' => 'Offer Details',
                'handle' => 'offer-details',
            ],
            [
                'name' => 'Payment Details',
                'handle' => 'payment',
            ],
            [
                'name' => 'Document Upload',
                'handle' => 'document-upload',
            ],
            [
                'name' => 'Sanction',
                'handle' => 'sanction',
            ],
            [
                'name' => 'GP Closed',
                'handle' => 'gp-closed',
            ]
        ];

        // Add timestamps
        $now = Carbon::now();
        foreach ($stages as $key => $stage) {
            $stage['stage_id'] = $key;
            $key++;
            $stage['is_active'] = 1;
            $stage['created_at'] = $now;
            $stage['updated_at'] = $now;
            $count =  DB::table('hj_master_cc_stage')->where('id', $key)->count();

            if ($count) {
                DB::table('hj_master_cc_stage')->where('id', $key)->update($stage);
            } else {
                DB::table('hj_master_cc_stage')->insert($stage);
            }
        }
    }
}
