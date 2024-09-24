<?php

namespace App\Repositories;

use App\Entities\HousingJourney\HjMasterEmploymentType;
use App\Entities\HousingJourney\HjMasterPropertyCurrentState;
use App\Entities\HousingJourney\HjMasterPropertyType;
use App\Entities\HousingJourney\HjMasterDocument;
use App\Entities\HousingJourney\HjMasterProduct;
use App\Entities\HousingJourney\HjMasterIndustrySegment;
use App\Entities\HousingJourney\HjMasterIndustryType;
use Exception;
use Throwable;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Log;
use App\Entities\HousingJourney\HjMasterDocumentType;
use App\Entities\HousingJourney\HjMappingDocumentType;
use App\Entities\HousingJourney\HjMasterProductType;
use App\Entities\HousingJourney\HjMasterProfessionalType;
use App\Entities\HousingJourney\HjMasterEmploymentSalaryMode;
use App\Entities\HousingJourney\HjMasterEmploymentConstitutionType;
use App\Entities\HousingJourney\HjMasterPropertyPurpose;

class MasterRepository
{
  /**
   * upsert Product data
   *
   * @param $request
   */
  public function upsertProductsDetails($request)
  {
    try {
      $productData = HjMasterProduct::where('code', $request['code'])->first();
      if ($productData) {
        HjMasterProduct::where(
          'code',
          $request['code']
        )->update([
          'name' => $request['name'],
          'display_name' => $request['display_name'],
          'product_id' => $request['product_id'], 'is_active' => $request['is_active']
        ]);
      } else {
        $productData = HjMasterProduct::create($request);
      }
      return $productData;
    } catch (Throwable | Exception | HttpClientException $throwable) {
      Log::info("upsertProducts : " . $throwable->__toString());
    }
  }
  /**
   * upsert ProductType data
   *
   * @param $request
   */
  public function upsertMasterProductType($request)
  {
    try {
      return HjMasterProductType::updateOrCreate(['name' => $request['name']], $request);
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("upsertMasterProductType : " . $throwable->__toString());
    }
  }
  /**
   * upsert industry type data
   *
   * @param $request 
   */
  public function upsertIndustryType($request)
  {
    try {
      $dataExist = HjMasterIndustryType::where('name', $request['name'])->count();
      if ($dataExist > 0) {
        HjMasterIndustryType::where('name', $request['name'])->update([
          'name' => $request['name'],
          'master_id' => $request['master_id'], 'is_active' => $request['is_active']
        ]);
      } else {
        HjMasterIndustryType::create($request);
      }
    } catch (Throwable | Exception | HttpClientException $throwable) {
      Log::info("upsertIndustryType : " . $throwable->__toString());
    }
  }
  /**
   * upsert industry segment data
   *
   * @param $request 
   */
  public function upsertIndustryTyp($request)
  {
    try {
      $dataExist = HjMasterIndustrySegment::where('name', $request['name'])->count();
      if ($dataExist > 0) {
        HjMasterIndustrySegment::where('name', $request['name'])->update([
          'name' => $request['name'],
          'master_id' => $request['master_id'], 'is_active' => $request['is_active']
        ]);
      } else {
        HjMasterIndustrySegment::create($request);
      }
    } catch (Throwable | Exception | HttpClientException $throwable) {
      Log::info("upsertIndustrySegment : " . $throwable->__toString());
    }
  }
  /**
   * upsert the employment type data
   *
   * @param $request 
   */
  public function upsertEmployeeType($request)
  {
    try {
      if ($request['name'] == "Self Employed Professinal") {
        $request['handle'] = "self-employed-professional";
      } elseif ($request['name'] == "Self Employed Non-Professinal") {
        $request['handle'] = "self-employed-non-professional";
      }
      $dataExist = HjMasterEmploymentType::where('name', $request['name'])->count();
      if ($dataExist > 0) {
        HjMasterEmploymentType::where('name', $request['name'])->update([
          'name' => $request['name'], 'master_id' => $request['master_id'], 'is_active' => $request['is_active']
        ]);
      } else {
        HjMasterEmploymentType::create($request);
      }
    } catch (Throwable | Exception | HttpClientException $throwable) {
      Log::info("upsertEmploymentType : " . $throwable->__toString());
    }
  }
  /**
   * upsert the property type data
   *
   * @param $request 
   */
  public function upsertPropertyType($request)
  {
    try {
      $dataExist = HjMasterPropertyType::where('name', $request['name'])->count();
      if ($dataExist > 0) {
        HjMasterPropertyType::where('name', $request['name'])->update([
          'name' => $request['name'], 'master_id' => $request['master_id'],
          'is_active' => $request['is_active'], 'product_code' => $request['product_code']
        ]);
      } else {
        HjMasterPropertyType::create($request);
      }
    } catch (Throwable | Exception | HttpClientException $throwable) {
      Log::info("upsertPropertyType : " . $throwable->__toString());
    }
  }
  /**
   * upsert the property cost data
   *
   * @param $request 
   */
  public function upsertPropertyCurrentSts($request)
  {
    try {
      $dataExist = HjMasterPropertyCurrentState::where('name', $request['name'])->count();
      if ($dataExist > 0) {
        HjMasterPropertyCurrentState::where('name', $request['name'])->update([
          'name' => $request['name'], 'display_name' => $request['display_name'], 'master_id' => $request['master_id'], 'is_active' => $request['is_active']
        ]);
      } else {
        HjMasterPropertyCurrentState::create($request);
      }
    } catch (Throwable | Exception | HttpClientException $throwable) {
      Log::info("upsertPropertyCurrentState : " . $throwable->__toString());
    }
  }
  /**
   * upsert document  data
   *
   * @param $request
   */
  public function upsertDocumentType($request)
  {
    try {
      $dataExist = HjMasterDocument::where('name', $request['name'])->count();
      if ($dataExist > 0) {
        HjMasterDocument::where(
          'name',
          $request['name']
        )->update(
          [
            'name' => $request['name'], 'is_active' => $request['is_active'],
            'max_file' => $request['max_file'],
            'max_size_per_file_mb' => $request['max_size_per_file_mb'],
            'allowed_extensions' => $request['allowed_extensions'],
            'master_id' => $request['master_id']
          ]
        );
      } else {
        HjMasterDocument::create($request);
      }
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("upsertDocumentType : " . $throwable->__toString());
    }
  }
  /**
   * upsert document types data
   *
   * @param $request
   */
  public function upsertMasterDocumentType($request)
  {
    try {
      HjMasterDocumentType::updateOrCreate(['name' => $request['name']], $request);
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("upsertMasterDocumentType : " . $throwable->__toString());
    }
  }
  /**
   * upsert mapping document
   *
   * @param $request
   **/
  public function upsertMappingDocumentType($request)
  {
    try {
      HjMappingDocumentType::updateOrCreate(
        ['master_document_type_id'
        => $request['master_document_type_id'], 'master_document_id' => $request['master_document_id']],
        $request
      );
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("upsertMappingDocumentType : " . $throwable->__toString());
    }
  }
  /**
   * upsert professional data
   *
   * @param $request
   **/
  public function upsertProfessionalType($request)
  {
    try {
      HjMasterProfessionalType::updateOrCreate(
        ['name' => $request['name']],
        $request
      );
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("upsertProfessionalType : " . $throwable->__toString());
    }
  }
  /**
   * upsert employee sales mode data
   *
   * @param $request
   **/
  public function upsertEmpSalMode($request)
  {
    try {
      HjMasterEmploymentSalaryMode::updateOrCreate(
        ['name' => $request['name']],
        $request
      );
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("upsertEmpSalMode : " . $throwable->__toString());
    }
  }
  /**
   * upsert employee constitution type data
   *
   * @param $request
   **/
  public function upsertEmpConstitueType($request)
  {
    try {
      HjMasterEmploymentConstitutionType::updateOrCreate(
        ['name' => $request['name']],
        $request
      );
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("upsertEmpConstitueType : " . $throwable->__toString());
    }
  }
  /**
   * upsert employee constitution type data
   *
   * @param $request
   **/
  public function upsertPropertyPurpose($request)
  {
    try {
      HjMasterPropertyPurpose::updateOrCreate(
        ['name' => $request['name']],
        $request
      );
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("upsertPropertyPurpose : " . $throwable->__toString());
    }
  }
  /**
   * get not existing profession type
   * 
   **/
  public function getExistingProfType($professionType)
  {
    try {
      return HjMasterProfessionalType::whereNotIn('name', $professionType)->get('id');
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("getExistingProfType : " . $throwable->__toString());
    }
  }
  /**
   * remove exist profession type
   * 
   **/
  public function removeExistProfType($existProfessionType)
  {
    try {
      return HjMasterProfessionalType::whereIn('id', $existProfessionType)->delete();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("removeExistProfType : " . $throwable->__toString());
    }
  }
  /**
   * get not existing current state
   *
   **/
  public function getExistingPropCurState($existCurrentState)
  {
    try {
      return HjMasterPropertyCurrentState::whereNotIn('name', $existCurrentState)->get('id');
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("getExistingPropCurState : " . $throwable->__toString());
    }
  }
  /**
   * remove exist profession type
   * @param $request
   **/
  public function removeExistPropCurState($existPropCurrentState)
  {
    try {
      return HjMasterPropertyCurrentState::whereIn('id', $existPropCurrentState)->delete();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("removeExistPropCurState : " . $throwable->__toString());
    }
  }
}
