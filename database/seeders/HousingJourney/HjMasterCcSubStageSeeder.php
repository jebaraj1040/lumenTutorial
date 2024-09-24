<?php

namespace Database\Seeders\HousingJourney;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HjMasterCcSubStageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $subStages = [
            [
                'name' => 'OTP Not Initiated',
                'handle' => 'otp-not-initiated',
                'priority' => 'HF-001',
                'block_for_calling' => 'YES',
            ],
            [
                'name' => 'OTP Generated',
                'handle' => 'otp-generated',
                'priority' => 'HF-002',
                'block_for_calling' => 'YES',
            ],
            [
                'name' => 'OTP Not Verified',
                'handle' => 'otp-not-verified',
                'priority' => 'HF-003',
                'block_for_calling' => 'YES',
            ],
            [
                'name' => 'OTP Verified',
                'handle' => 'otp-verified',
                'priority' => 'HF-004',
                'block_for_calling' => 'NO',
            ],
            [
                'name' => 'Personal Details - Applicant Pending',
                'handle' => 'personal-details-applicant-pending',
                'priority' => 'HF-101',
                'block_for_calling' => 'NO',
            ],
            [
                'name' => 'Employment Details - Applicant Pending',
                'handle' => 'employment-applicant-pending',
                'priority' => 'HF-201',
                'block_for_calling' => 'NO',
            ],
            [
                'name' => 'Eligibility - Applicant BRE Success',
                'handle' => 'eligibility-applicant-bre-success',
                'priority' => 'HF-301',
                'block_for_calling' => 'NO',
            ],
            [
                'name' => 'Eligibility - Applicant BRE Failed',
                'handle' => 'eligibility-applicant-bre-failed',
                'priority' => 'HF-302',
                'block_for_calling' => 'NO',
            ],
            [
                'name' => 'Personal Details - Co-applicant Pending',
                'handle' => 'personal-details-co-applicant-pending',
                'priority' => 'HF-401',
                'block_for_calling' => 'NO',
            ],
            [
                'name' => 'Employment Details - Co-applicant Pending',
                'handle' => 'co-applicant-employment-details-pending',
                'priority' => 'HF-501',
                'block_for_calling' => 'NO',
            ],

            [
                'name' => 'Eligibility - Co-applicant BRE Success',
                'handle' => 'eligibility-co-applicant-bre-success',
                'priority' => 'HF-601',
                'block_for_calling' => 'NO',
            ],
            [
                'name' => 'Eligibility - Co-applicant BRE Failed',
                'handle' => 'eligibility-co-applicant-bre-failed',
                'priority' => 'HF-602',
                'block_for_calling' => 'NO',
            ],
            [
                'name' => 'Address Details Pending',
                'handle' => 'address-details-pending',
                'priority' => 'HF-701',
                'block_for_calling' => 'NO',
            ],
            [
                'name' => 'Property Details Pending',
                'handle' => 'property-details-pending',
                'priority' => 'HF-801',
                'block_for_calling' => 'NO',
            ],
            [
                'name' => 'Offer Details - Final Voucher Page',
                'handle' => 'offer-details-final-voucher-page',
                'priority' => 'HF-901',
                'block_for_calling' => 'NO',
            ],
            [
                'name' => 'Offer Details - Failed No Voucher',
                'handle' => 'offer-details-failed-no-voucher',
                'priority' => 'HF-902',
                'block_for_calling' => 'NO',
            ],
            [
                'name' => 'Payment Attempted',
                'handle' => 'payment-attempted',
                'priority' => 'HF-1001',
                'block_for_calling' => 'NO',
            ],
            [
                'name' => 'Payment Pending',
                'handle' => 'payment-pending',
                'priority' => 'HF-1002',
                'block_for_calling' => 'NO',
            ],
            [
                'name' => 'Payment Failure',
                'handle' => 'payment-failure',
                'priority' => 'HF-1003',
                'block_for_calling' => 'NO',
            ],
            [
                'name' => 'Payment Success',
                'handle' => 'payment-success',
                'priority' => 'HF-1004',
                'block_for_calling' => 'YES',
            ],
            [
                'name' => 'Document Upload Pending',
                'handle' => 'document-upload-pending',
                'priority' => 'HF-1101',
                'block_for_calling' => 'NO',
            ],


            [
                'name' => 'Sanction Not Generated',
                'handle' => 'sanction-not-generated',
                'priority' => 'HF-1201',
                'block_for_calling' => 'YES',
            ],
            [
                'name' => 'Sanction Generated',
                'handle' => 'sanction-generated',
                'priority' => 'HF-1202',
                'block_for_calling' => 'YES',
            ],
            [
                'name' => 'GP Closed - Interested',
                'handle' => 'gp-closed-interested',
                'priority' => 'HF-1301',
                'block_for_calling' => 'YES',
            ],
            [
                'name' => 'GP Closed - Payment Done & Document Pending',
                'handle' => 'gp-closed-payment-done-and-document-pending',
                'priority' => 'HF-1302',
                'block_for_calling' => 'YES',
            ],
            [
                'name' => 'GP Closed - Payment Done & Document Upload Done',
                'handle' => 'gp-closed-payment-done-and-document-upload-done',
                'priority' => 'HF-1303',
                'block_for_calling' => 'YES',
            ],
            [
                'name' => 'GP Closed - Document Upload Done',
                'handle' => 'gp-closed-document-upload-done',
                'priority' => 'HF-1304',
                'block_for_calling' => 'YES',
            ],
            [
                'name' => 'GP Closed - Final Submit',
                'handle' => 'gp-closed-final-submit',
                'priority' => 'HF-1305',
                'block_for_calling' => 'YES',
            ],
        ];

        // Add timestamps
        $now = Carbon::now();
        foreach ($subStages as $key => $subStage) {
            $key++;
            $subStage['is_active'] = 1;
            $subStage['created_at'] = $now;
            $subStage['updated_at'] = $now;

            $count =  DB::table('hj_master_cc_sub_stage')->where('id', $key)->count();

            if ($count) {
                DB::table('hj_master_cc_sub_stage')->where('id', $key)->update($subStage);
            } else {
                DB::table('hj_master_cc_sub_stage')->insert($subStage);
            }
        }
    }
}
