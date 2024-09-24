<?php

namespace App\Repositories\HousingJourney;

use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\HttpClientException;
use App\Entities\HousingJourney\HjMasterDocument;
use App\Entities\HousingJourney\HjMasterDocumentType;

class MasterDocumentRepository
{
  /**
   * insert into document type table
   *
   */
  public function save($data)
  {
    try {
      return HjMasterDocument::updateOrCreate(['name' => $data['name']], $data);
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("MasterDocumentRepository save " . $throwable->__toString());
    }
  }

  public function getMasterDocumentTypeId($docName)
  {
    try {
      return HjMasterDocumentType::where('name', $docName)->value('id');
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("getMasterDocumentTypeId " . $throwable->__toString());
    }
  }

  public function getMasterDocumentId($docName)
  {
    try {
      return HjMasterDocument::where('name', $docName)->value('id');
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("getMasterDocumentId " . $throwable->__toString());
    }
  }

  public function getDocumentDropdown()
  {
    try {
      return HjMasterDocumentType::with('documentDropDownList')->where('handle', 'income-proof')->get();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("getMasterDocumentId " . $throwable->__toString());
    }
  }
}
