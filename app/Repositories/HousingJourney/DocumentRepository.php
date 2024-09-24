<?php

namespace App\Repositories\HousingJourney;

use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Http\Client\HttpClientException;
use App\Entities\HousingJourney\HjDocument;
use App\Entities\HousingJourney\HjMasterDocument;
use App\Utils\CrmTrait;

class DocumentRepository
{
  use CrmTrait;
  /**
   * get document type
   *
   */
  public function getDocumentName($request)
  {
    try {
      return HjMasterDocument::where('id', $request->master_document_id)->value('name');
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("getDocumentName " . $throwable->__toString());
    }
  }
  /**
   * save document
   *
   */
  public function save($request)
  {
    try {
      return HjDocument::create($request);
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("DocumentRepository save " . $throwable->__toString());
    }
  }
  /**
   * View document
   *
   */
  public function view($request)
  {
    try {
      return HjDocument::Select('id', 'master_document_id', 'master_document_type_id', 'document_type_extension', 'document_file_name', 'document_position_id')->with('document:handle,id,name')
        ->where('lead_id', $request['lead_id'])
        ->where('quote_id', $request['quote_id'])->get();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("DocumentRepository view" . $throwable->__toString());
    }
  }

  /**
   * View document
   *
   */
  public function viewForFinalSumbit($request)
  {
    try {
      return HjDocument::Select(
        'id',
        'master_document_id',
        'master_document_type_id',
        'document_type_extension',
        'document_file_name',
        'document_position_id',
        'document_saved_location'
      )->with('document:handle,id,name,master_id')
        ->where('lead_id', $request['lead_id'])
        ->where('quote_id', $request['quote_id'])->get();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("DocumentRepository viewForFinalSumbit " . $throwable->__toString());
    }
  }


  /**
   * View document
   *
   */
  public function getDocument($request)
  {
    try {
      return HjDocument::Select('document_saved_location', 'document_file_name', 'id', 'master_document_id', 'master_document_type_id', 'document_type_extension',   'document_position_id')->with('document')
        ->where('lead_id', $request['lead_id'])
        ->where('quote_id', $request['quote_id'])->get();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("DocumentRepository view " . $throwable->__toString());
    }
  }


  /**
   * Document List
   *
   */
  public function documentList($request, $type)
  {
    try {
      return HjDocument::Select('master_document_id', 'master_document_type_id', 'document_type_extension', 'document_saved_location', 'document_file_name', 'document_encrypted_name', 'document_position_id')->with('document')
        ->where('lead_id', $request['lead_id'])
        ->where('quote_id', $request['quote_id'])
        ->where('master_document_type_id', $type)->get();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("DocumentRepository view " . $throwable->__toString());
    }
  }

  /**
   * get Document Data
   *
   */
  public function getDocumentData($request)
  {
    try {
      return HjDocument::where('id', $request['id'])
        ->where('quote_id', $request['quote_id'])->get();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("DocumentRepository getDocumentData " . $throwable->__toString());
    }
  }

  /**
   * get Document List
   *
   */
  public function getDocumentList($request)
  {
    try {
      return HjDocument::where('lead_id', $request['lead_id'])
        ->where('quote_id', $request['quote_id'])
        ->where('master_document_type_id', $request['document_type_id'])->get();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("DocumentRepository getDocumentList " . $throwable->__toString());
    }
  }

  /**
   * Remove Document List
   *
   */
  public function removeDocs($request)
  {
    try {
      return HjDocument::where('lead_id', $request['lead_id'])
        ->where('quote_id', $request['quote_id'])
        ->where('master_document_type_id', $request['document_type_id'])->delete();
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("DocumentRepository removeDocs " . $throwable->__toString());
    }
  }

  /**
   * Remove Document data
   *
   */
  public function removeDocumentData($request)
  {
    try {
      return HjDocument::where('id', $request['id'])
        ->where('quote_id', $request['quote_id'])->delete();
    } catch (Throwable | HttpClientException $throwable) {
      Log::info("DocumentRepository removeDocumentData " . $throwable->__toString());
    }
  }
  public function list($request, $offset = null)
  {
    try {
      $query = HjDocument::query();
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
      $documnetData = $query->select('*')
        ->with('leadDetail:id,mobile_number')
        ->with('document:id,name')
        ->with('documentType:id,name')
        ->orderBy('id', 'desc')
        ->get();
      if ($request->action == 'download') {
        foreach ($documnetData as $key => $item) {
          // Check if document is loaded and not null
          if ($item->document) {
            $documnetData[$key]['master_document_id'] =  $item->document->name;
          } else {
            $documnetData[$key]['master_document_id'] = null;
          }
          // Check if documentType is loaded and not null
          if ($item->documentType) {
            $documnetData[$key]['master_document_type_id'] =  $item->documentType->name;
          } else {
            $documnetData[$key]['master_document_type_id'] = null;
          }
          // Check if leadDetail is loaded and not null
          if ($item->leadDetail) {
            $documnetData[$key]['lead_id'] =  $item->leadDetail->mobile_number;
          } else {
            $documnetData[$key]['lead_id'] = null;
          }
          unset($documnetData[$key]['document']);
          unset($documnetData[$key]['documentType']);
          unset($documnetData[$key]['leadDetail']);
        }
      }
      $documentDetailData['totalLength'] =  $totalLength;
      $documentDetailData['dataList'] = $documnetData;
      return $documentDetailData;
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("DocumentRepository list " . $throwable->__toString());
    }
  }
}
