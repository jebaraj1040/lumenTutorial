<?php

namespace App\Repositories\HousingJourney;

use App\Entities\HousingJourney\HjLead;
use App\Entities\HousingJourney\HjMappingCoApplicant;
use Illuminate\Support\Facades\Log;
use Throwable;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Client\HttpClientException;
use App\Entities\HousingJourney\HjMasterRelationship;
use App\Entities\HousingJourney\HjMappingApplicantRelationship;
use App\Entities\HousingJourney\HjApplication;
use App\Entities\HousingJourney\HjMasterPincode;
use App\Utils\CrmTrait;
use Exception;

class LeadRepository
{
    use CrmTrait;
    /**
     * Insert or update lead.
     */
    public function save($request)
    {
        try {
            return HjLead::updateOrCreate([
                'mobile_number' => $request['mobile_number'],
                'is_applicant' => $request['is_applicant']
            ], $request);
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("LeadRepository save " . $throwable->__toString());
        }
    }
    /**
     * Get lead Deatils.
     */
    public function getLeadDetailsById($leadId)
    {
        try {
            return HjLead::where('id', $leadId)->select('name', 'mobile_number')->first();
        } catch (Throwable  | ClientException $throwable) {
            Log::info("Repo-getLeadDetailsById" . $throwable->__toString());
        }
    }
    /**
     * get lead
     */
    public function view($leadId)
    {
        try {
            $query = HjLead::query();
            $query->with('pincodeData')->where('id', $leadId)->select(
                'id',
                'name',
                'mobile_number',
                'pincode_id',
                'is_being_assisted',
                'partner_code',
                'home_extension',
                'sub_partner_code',
                'is_agreed',
                'customer_type',
                'partner_name'
            );
            return $query->first();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("LeadRepository view " . $throwable->__toString());
        }
    }

    /**
     * get lead
     */
    public function getLead($leadId)
    {
        try {
            $query = HjLead::query();
            $query->with('pincodeData')->where('id', $leadId['lead_id'])->select(
                'id',
                'name',
                'mobile_number',
                'pincode_id',
                'is_being_assisted',
                'partner_code',
                'home_extension',
                'sub_partner_code',
                'is_agreed'
            );
            return $query->first();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("LeadRepository view " . $throwable->__toString());
        }
    }
    /**
     * get lead name
     */
    public function getLeadName($request)
    {
        try {
            return HjLead::where('id', $request['lead_id'])->value('name');
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("LeadRepository getLeadName " . $throwable->__toString());
        }
    }
    /**
     * Insert co applicant.
     */
    public function saveCoApplicant($request)
    {
        try {
            return HjMappingCoApplicant::updateOrInsert(
                [
                    'lead_id' => $request['lead_id'],
                    'quote_id' => $request['quote_id'], 'co_applicant_id' => $request['co_applicant_id']
                ],
                $request
            );
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("LeadRepository saveCoApplicant " . $throwable->__toString());
        }
    }
    /**
     * get co-applicant data
     */
    public function getCoApplicantData($request)
    {
        try {
            $coApplicant = HjMappingCoApplicant::query();
            $coApplicant->where('lead_id', $request['lead_id'])->where('quote_id', $request['quote_id']);
            return $coApplicant->first();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("LeadRepository getCoApplicantData" . $throwable->__toString());
        }
    }
    /**
     * get relationship master data
     */
    public function getMasterRelationshipData()
    {
        try {
            return HjMasterRelationship::select('id', 'name', 'handle')->where('is_active', '1')->get();
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("LeadRepository getMasterRelationshipData" . $throwable->__toString());
        }
    }
    /**
     * coapplicant relationship save
     */
    public function relationshipSave($request)
    {
        try {
            return HjMappingApplicantRelationship::updateOrCreate(
                ['lead_id' => $request['lead_id'], 'quote_id' => $request['quote_id']],
                $request
            );
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("LeadRepository relationshipSave " . $throwable->__toString());
        }
    }
    /**
     * get co-applicant id
     */
    public function getCoApplicantId($request)
    {
        try {
            return HjMappingCoApplicant::where('lead_id', $request['lead_id'])->value('co_applicant_id');
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("LeadRepository getCoApplicantId " . $throwable->__toString());
        }
    }
    /**
     * list leads from Table
     * @param $request
     */
    public function list($request, $offset = null)
    {
        try {
            $query = HjLead::query();
            $query = $this->applyFilter($query, $request, 'Leads');
            if ($request->search != '' && $request->search != 'null') {
                $keyword = $request->search;
                $query->where(function ($query) use ($keyword) {
                    $query->orWhere('hj_lead.mobile_number', $keyword);
                    $query->orWhere('hj_lead.name', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('hj_lead.id', $keyword);
                });
            }
            $totalLength = $query->count();
            if ($request->action != 'download') {
                $skip = intval($request->skip);
                $limit = intval($request->limit);
                $query->skip($skip)->limit($limit);
            }
            if (empty($offset === false) && $offset != 'null' && $offset != '') {
                $limit = (int)env('EXPORT_EXCEL_LIMIT');
                $query->offset($offset)->limit($limit);
            }
            $leadDetail = $query->select('*')
                ->with('pincodeData:id,code')
                ->orderBy('id', 'desc')
                ->get();

            foreach ($leadDetail as $key => $item) {
                // Check if pincodeData is loaded and not null
                if ($item->pincodeData) {
                    $leadDetail[$key]['pincode_id'] =  $item->pincodeData->code;
                } else {
                    $leadDetail[$key]['pincode_id'] = null;
                }
                unset($leadDetail[$key]['pincodeData']);
            }

            $leadDetailData['totalLength'] =  $totalLength;
            $leadDetailData['dataList'] = $leadDetail;
            return $leadDetailData;
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("LeadRepository list " . $throwable->__toString());
        }
    }
    public function detail($applicantId)
    {
        try {
            $query = HjLead::query();
            $leadDetail = $query->where('id', $applicantId->leadId)->first();
            $totalLength = $query->count();
            $leadDetail['length'] = $totalLength;
            return $leadDetail;
        } catch (Throwable  | ClientException $throwable) {
            Log::info("detail " . $throwable->__toString());
        }
    }
    /**
     * Get Sanction Letter  Data.
     */
    public function getSLData($quoteId)
    {
        try {
            return HjApplication::select('lead_id', 'quote_id', 'payment_transaction_id', 'digital_transaction_no', 'bre1_updated_loan_amount', 'bre1_loan_amount', 'bre2_loan_amount', 'is_paid', 'master_product_id', 'is_bre_execute', 'created_at')->with('eligibilityData:is_co_applicant,id,is_deviation,lead_id,loan_amount,tenure,type,quote_id',  'lead:name,id', 'personaldetail:quote_id,full_name')->where('quote_id', $quoteId)->first();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("LeadRepository getSLData" . $throwable->__toString());
        }
    }
    /**
     * update customer status
     */
    public function updateCustomerType($requestData)
    {
        try {
            HjLead::where('id', $requestData['lead_id'])
                ->update(['customer_type' => $requestData['customer_type']]);
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("LeadRepository updateCustomerType " . $throwable->__toString());
        }
    }


    public function getPincode($pincodeId)
    {
        try {
            return HjMasterPincode::where('id', $pincodeId)->value('code');
        } catch (Throwable  | ClientException $throwable) {
            Log::info("getPincode : " . $throwable->__toString());
        }
    }
    /**
     * update lead status
     */
    public function updateOtpStatus($request)
    {
        try {
            return HjLead::where('mobile_number', $request['mobile_number'])->where('is_applicant', 1)->update(['is_otp_verified' => $request['is_otp_verified']]);
        } catch (Throwable  | ClientException $throwable) {
            Log::info("updateOtpStatus : " . $throwable->__toString());
        }
    }
}
