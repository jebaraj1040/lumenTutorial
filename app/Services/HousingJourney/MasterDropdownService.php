<?php

namespace App\Services\HousingJourney;

use App\Repositories\HousingJourney\ProductTypeMasterRepository;
use App\Repositories\HousingJourney\MasterPropertyCurrentStateRepository;
use App\Services\Service;
use Illuminate\Http\Request;
use Throwable;
use Exception;
use Illuminate\Http\Client\HttpClientException;
use App\Repositories\HousingJourney\EmploymentDetailRepository;
use Illuminate\Support\Facades\Log;

class MasterDropdownService extends Service
{

      /**
       * view property loan detail.
       *
       */
      public function getPropertyPageDropdown(Request $request)
      {
            try {
                  $ptRep = new ProductTypeMasterRepository();
                  $csRepo = new MasterPropertyCurrentStateRepository();
                  $propertyDetail['property_type'] = $ptRep->getPropertyType($request);
                  $propertyDetail['current_state'] = $csRepo->getCurrentStateData($request);
                  $propertyDetail['purpose_list'] = $csRepo->getPurposeData($request);
                  $propertyDetail['project_name'] = $csRepo->getProjectList($request);
                  return $this->responseJson(config('journey/http-status.success.status'), config('journey/http-status.success.message'), config('journey/http-status.success.code'), $propertyDetail);
            } catch (Throwable | Exception | HttpClientException $throwable) {
                  Log::info("MasterDropdownService -  getPropertyPageDropdown " . $throwable);
            }
      }
      /**
       * get employment dropdown list
       *
       */
      public function getEmploymentMasterData(EmploymentDetailRepository $employmentDetailsRepo)
      {
            try {
                  $masterData['salary_mode'] = $employmentDetailsRepo->getSalaryMode();
                  $masterData['employment_type'] = $employmentDetailsRepo->getEmploymentType();
                  $masterData['constitution_type'] = $employmentDetailsRepo->getConstitutionType();
                  $masterData['industry_segment'] = $employmentDetailsRepo->getIndustrySegment();
                  $masterData['professional_type'] = $employmentDetailsRepo->getProfessionalType();
                  return $this->responseJson(
                        config('journey/http-status.success.status'),
                        config('journey/http-status.success.message'),
                        config('journey/http-status.success.code'),
                        $masterData
                  );
            } catch (Throwable | Exception | HttpClientException $throwable) {
                  Log::info("MasterDropdownService -  getEmploymentMasterData " . $throwable);
            }
      }
      /**
       * get company dropdown list
       *
       */
      public function getCompanyMasterData(Request $request, EmploymentDetailRepository $empDtRepo)
      {
            try {
                  $companyMasterData = $empDtRepo->getCompanyData($request);
                  return $this->responseJson(
                        config('journey/http-status.success.status'),
                        config('journey/http-status.success.message'),
                        config('journey/http-status.success.code'),
                        $companyMasterData
                  );
            } catch (Throwable  | HttpClientException $throwable) {
                  Log::info("MasterDropdownService -  getCompanyMasterData " . $throwable);
            }
      }
}
