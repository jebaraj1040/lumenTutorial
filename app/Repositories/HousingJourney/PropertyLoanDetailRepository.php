<?php

namespace App\Repositories\HousingJourney;

use Throwable;
use App\Entities\HousingJourney\HjMasterProject;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Log;
use App\Entities\HousingJourney\HjPropertyLoanDetail;
use App\Entities\HousingJourney\HjMasterPropertyType;
use App\Entities\HousingJourney\HjMasterIfsc;
use App\Entities\HousingJourney\HjMasterPropertyCurrentState;
use App\Entities\HousingJourney\HjMasterPincode;
use App\Utils\CrmTrait;

class PropertyLoanDetailRepository
{
    use CrmTrait;
    /**
     * Insert propert loan details.
     *
     */
    public function save($request)
    {
        try {
            return HjPropertyLoanDetail::updateOrCreate(['lead_id' => $request['lead_id'], 'quote_id' => $request['quote_id']], $request);
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("PropertyLoanDetailRepository save " . $throwable->__toString());
        }
    }

    /**
     * Get property loan details.
     *
     */
    public function view($request)
    {
        try {
            return HjPropertyLoanDetail::with(
                'propertyPurpose',
                'project',
                'propertyType',
                'propertyCurrentState',
                'pincodeDetail',
                'loanProvider'
            )->where(
                'lead_id',
                $request['lead_id']
            )->where('quote_id', $request['quote_id'])->first();
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("PropertyLoanDetailRepository view" . $throwable->__toString());
        }
    }


    /**
     * Get property loan details.
     *
     */
    public function getPropertyExistingLoanData($request)
    {
        try {
            return HjPropertyLoanDetail::select('is_property_loan_free', 'is_existing_property', 'outstanding_loan_amount')->where(
                'lead_id',
                $request['lead_id']
            )->where('quote_id', $request['quote_id'])->first();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("PropertyLoanDetailRepository getPropertyExistingLoanData " . $throwable->__toString());
        }
    }


    /**
     * Fetch property loan details.
     *
     */
    public function fetchPropertyData($request)
    {
        try {
            return HjPropertyLoanDetail::with(
                'propertyPurpose:handle,name,id',
                'project:id,builder,builder_handle,code,name,pincode_id',
                'propertyType:handle,id,name',
                'propertyCurrentState:display_name,handle,id,name',
                'pincodeDetail:area,city,code,district,id,state',
                'loanProvider:bank_code,bank_name,id,ifsc,location,state,refpk'
            )->where('quote_id', $request['quote_id'])->first();
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("PropertyLoanDetailRepository fetchPropertyData" . $throwable->__toString());
        }
    }

    /**
     * Get property type
     *
     */
    public function getPropertyType($id)
    {
        try {
            return HjMasterPropertyType::find($id);
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("PropertyLoanDetailRepository getPropertyType" . $throwable->__toString());
        }
    }
    public function list($request, $offset = null)
    {
        try {
            $query = HjPropertyLoanDetail::query();
            $query = $this->applyFilter($query, $request);
            if (isset($request->search) && $request->search != '' && $request->search != 'null') {
                $keyword = $request->search;
                $query->where('quote_id', $keyword);
                $query->orWhere('lead_id', $keyword);
                $query->orWhereHas('leadDetail', function ($subquery) use ($keyword) {
                    $subquery->where('mobile_number', $keyword);
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
            $propertyLoanData = $query->select('*')
                ->with('leadDetail:id,mobile_number')
                ->with('loanProvider:id,bank_name')
                ->with('project:id,name')
                ->with('propertyCurrentState:id,name')
                ->with('productMaster:name')
                ->with('pincodeDetail:id,code')
                ->with('propertyType:id,name')
                ->orderBy('id', 'desc')
                ->get();
            if ($request->action == 'download') {
                foreach ($propertyLoanData as $key => $item) {
                    // Check if loanProvider is loaded and not null
                    if ($item->loanProvider) {
                        $propertyLoanData[$key]['existing_loan_provider'] =  $item->loanProvider->bank_name;
                    } else {
                        $propertyLoanData[$key]['existing_loan_provider'] = null;
                    }
                    // Check if propertyCurrentState is loaded and not null
                    if ($item->propertyCurrentState) {
                        $propertyLoanData[$key]['property_current_state_id'] =  $item->propertyCurrentState->name;
                    } else {
                        $propertyLoanData[$key]['property_current_state_id'] = null;
                    }
                    // Check if project is loaded and not null
                    if ($item->project) {
                        $propertyLoanData[$key]['project_id'] =  $item->project->name;
                    } else {
                        $propertyLoanData[$key]['project_id'] = null;
                    }
                    // Check if propertyType is loaded and not null
                    if ($item->propertyType) {
                        $propertyLoanData[$key]['property_type_id'] =  $item->propertyType->name;
                    } else {
                        $propertyLoanData[$key]['property_type_id'] = null;
                    }
                    // Check if pincodeDetail is loaded and not null
                    if ($item->pincodeDetail) {
                        $propertyLoanData[$key]['pincode_id'] =  $item->pincodeDetail->name;
                    } else {
                        $propertyLoanData[$key]['pincode_id'] = null;
                    }
                    // Check if leadDetail is loaded and not null
                    if ($item->leadDetail) {
                        $propertyLoanData[$key]['lead_id'] =  $item->leadDetail->mobile_number;
                    } else {
                        $propertyLoanData[$key]['lead_id'] = null;
                    }
                    unset($propertyLoanData[$key]['loanProvider']);
                    unset($propertyLoanData[$key]['leadDetail']);
                    unset($propertyLoanData[$key]['propertyCurrentState']);
                    unset($propertyLoanData[$key]['project']);
                    unset($propertyLoanData[$key]['productMaster']);
                    unset($propertyLoanData[$key]['pincodeDetail']);
                    unset($propertyLoanData[$key]['propertyType']);
                }
            }
            $propertyLoanDetailData['totalLength'] =  $totalLength;
            $propertyLoanDetailData['dataList'] = $propertyLoanData;
            return $propertyLoanDetailData;
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("propertyLoanDetailRepository list " . $throwable->__toString());
        }
    }
    /*  search project*/
    public function projectSearch($projectName)
    {
        try {
            return HjMasterProject::where('name', 'LIKE', '%' . $projectName . '%')->get();
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("PropertyLoanDetailRepository projectSearch " . $throwable->__toString());
        }
    }
    /**
     * get property id using code
     *
     */
    public function getPropertyTypeId($propertyCode)
    {
        try {
            return HjMasterPropertyType::where('product_code', $propertyCode)->value('id');
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("PropertyLoanDetailRepository getPropertyTypeId " . $throwable->__toString());
        }
    }
    /**
     * get property current state using code
     *
     */
    public function getPropertyCurrentStateId($currentState)
    {
        try {
            return HjMasterPropertyCurrentState::where('master_id', $currentState)->value('id');
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("PropertyLoanDetailRepository getPropertyCurrentStateId " . $throwable->__toString());
        }
    }
    /**
     * get pincode id using value
     *
     */
    public function getPincodeId($code)
    {
        try {
            return HjMasterPincode::where('code', $code)->value('id');
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("PropertyLoanDetailRepository getPincodeId " . $throwable->__toString());
        }
    }
    /**
     * remove existing property
     *
     */
    public function removeExistPropData($reqData)
    {
        try {
            HjPropertyLoanDetail::where('lead_id', $reqData['lead_id'])
                ->where('quote_id', $reqData['quote_id'])
                ->where('is_existing_property', 1)->delete();
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("PropertyLoanDetailRepository removeExistPropData " . $throwable->__toString());
        }
    }
}
