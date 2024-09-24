<?php

namespace Database\Seeders\HousingJourney;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HjMasterProductStepSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $steps = [
            [
                'name' => 'OTP Verification',
                'handle' => 'otp-verification',
                'percentage' => "10%",
            ],
            [
                'name' => 'Personal Details',
                'handle' => 'personal-details',
                'percentage' => "15%",
            ],
            [
                'name' => 'Employment Details',
                'handle' => 'employment-details',
                'percentage' => "20%",
            ],
            [
                'name' => 'Applicant Eligibility',
                'handle' => 'applicant-eligibility',
                'percentage' => "30%",
            ],
            [
                'name' => 'Co Applicant Personal Details',
                'handle' => 'co-applicant-personal-details',
                'percentage' => "35%",
            ],
            [
                'name' => 'Co Applicant Employment Details',
                'handle' => 'co-applicant-employment-details',
                'percentage' => "35%",
            ],
            [
                'name' => 'Co Applicant Eligibility',
                'handle' => 'co-applicant-eligibility',
                'percentage' => "40%",
            ],
            [
                'name' => 'Address Details',
                'handle' => 'address-details',
                'percentage' => "50%",
            ],
            [
                'name' => 'Property Loan Details',
                'handle' => 'property-loan-details',
                'percentage' => "55%",
            ],
            [
                'name' => 'Offer Details',
                'handle' => 'offer-details',
                'percentage' => "70%",
            ],
            [
                'name' => 'Payment Level 1',
                'handle' => 'payment-l1',
                'percentage' => "80%",
            ],
            [
                'name' => 'Document Upload',
                'handle' => 'document-upload',
                'percentage' => "90%",
            ],
            [
                'name' => 'Sanction Letter',
                'handle' => 'sanction-letter',
                'percentage' => "90%",
            ],
            [
                'name' => 'Payment Level 2',
                'handle' => 'payment-l2',
                'percentage' => "90%",
            ],
            [
                'name' => 'Congratulation',
                'handle' => 'congratulation',
                'percentage' => "100%",
            ],
        ];

        // Add timestamps
        $now = Carbon::now();
        foreach ($steps as &$step) {
            $step['is_active'] = 1;
            $step['created_at'] = $now;
            $step['updated_at'] = $now;
        }

        // Insert data
        DB::table('hj_master_product_step')->insert($steps);
    }
}
