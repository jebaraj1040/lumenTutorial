<?php

namespace App\Services\HousingJourney;

use App\Repositories\HousingJourney\LeadRepository;
use App\Repositories\HousingJourney\ApplicationRepository;
use App\Repositories\HousingJourney\ImpressionRepository;
use App\Repositories\HousingJourney\CoreRepository;
use App\Services\Service;
use Throwable;
use Exception;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Repositories\HousingJourney\DocumentRepository;
use Illuminate\Support\Facades\Storage;
use App\Utils\CrmTrait;
use App\Utils\CommonTrait;
use App\Utils\CoreTrait;
use Illuminate\Support\Facades\Log;


class IncreaseEligibilityDocumentService extends Service
{
     use CrmTrait;
     use CommonTrait;
     use CoreTrait;
     /**
      * Save Document 
      *
      */
     public function save(Request $request, DocumentRepository $increseEligibilityDocumentRepo, LeadRepository $leadRepo, ImpressionRepository $impressionRepo, ApplicationRepository $applicationRepo, CoreRepository $coreRepo)
     {
          try {
               $rules = [
                    "file" => "required|max:5120",
               ];
               $validator = Validator::make($request->all(), $rules);
               if ($validator->fails()) {
                    return  $this->fileExceptionhandle("size");
               }
               $file = $request->file;
               $contentType =  ["pdf"];
               $fileMime['type'] = $request->file->getMimeType();
               $documentName  = $increseEligibilityDocumentRepo->getDocumentName($request);
               $extArray  = explode('.', $file->getClientOriginalName());
               if (count($extArray) >= 3) {
                    return  $this->fileExceptionhandle("format");
               }
               $leadName  = $leadRepo->getLeadName($request);
               if ($file) {
                    if ($fileMime['type'] == "application/pdf") {
                         $ext = strtolower($file->getClientOriginalExtension());
                         if (in_array($ext, $contentType)) {
                              $currtime = Carbon::now()->timestamp;
                              $initFileName = $request['quote_id'] . $leadName . $currtime;
                              $fileName = $initFileName . "." . $request->file->extension();
                              $fileName =   $this->handlePreparation($fileName);
                              $filePath = '/shfl/upload-documents/' . $request['quote_id'] . '/' . $documentName . '/' . $fileName;
                              $filePath =   $this->handlePreparation($filePath);
                              Storage::disk('s3')->put($filePath, file_get_contents($file), 'public');
                              $uploadFileName = 'https://' . env('S3_BUCKET') . $filePath;
                              $request['document_saved_location'] = $uploadFileName;
                              $request['document_file_name'] = $file->getClientOriginalName();
                              $request['document_type_extension'] = $ext;
                              $request['document_encrypted_name'] = $fileName;
                              // save into document Table
                              $updateProfile = $increseEligibilityDocumentRepo->save($request->all());
                              $documentData = $increseEligibilityDocumentRepo->view($request);
                              if ($updateProfile) {
                                   return $this->responseJson(config('journey/http-status.update.status'), config('journey/http-status.update.message'), config('journey/http-status.update.code'), $documentData);
                              } else {
                                   return $this->fileExceptionHandle("error");
                              }
                         }
                    } else {
                         return  $this->fileExceptionHandle("format");
                    }
               } else {
                    return  $this->fileExceptionHandle("format");
               }
          } catch (Throwable | Exception | HttpClientException $throwable) {
               Log::info("IncreaseEligibilityDocumentService -  save " . $throwable);
               return $this->responseJson(
                    config('journey/http-status.error.status'),
                    config('journey/http-status.error.message'),
                    config('journey/http-status.error.code'),
                    []
               );
          }
     }

     public function fileExceptionHandle($type)
     {
          if ($type == "format") {
               return $this->responseJson(config('journey/http-status.bad-request.status'), "File format must be PDF", config('journey/http-status.bad-request.code'), []);
          } elseif ($type == "size") {
               return $this->responseJson(config('journey/http-status.bad-request.status'), "File Required or File size exceeds the allowed limit (5MB)", config('journey/http-status.bad-request.code'), []);
          } elseif ($type == "error") {
               return $this->responseJson(config('journey/http-status.error.status'), config('journey/http-status.error.message'), config('journey/http-status.error.code'), []);
          }
     }

     /**
      * Multi Files Remove
      
      *
      */
     public function filesRemove(Request $request)
     {
          try {
               $docRepo = new DocumentRepository();
               $documentData = $docRepo->getDocumentList($request);
               $docStatus =  $this->checkFileExistInS3($documentData);
               if (!$docStatus) {
                    return $this->responseJson(config('journey/http-status.failure.status'), config('journey/http-status.failure.message'), config('journey/http-status.failure.code'), []);
               }
               $docRepo->removeDocs($request);
               return $this->responseJson(config('journey/http-status.update.status'), "File Sucessfully Deleted", config('journey/http-status.update.code'), []);
          } catch (\Throwable $throwable) {
               Log::info("IncreaseEligibilityDocumentService -  filesRemove " . $throwable);
          }
     }


     /**
      * Single File Remove
      
      *
      */
     public function fileRemove(Request $request)
     {
          try {
               $docRepo = new DocumentRepository();
               $documentData = $docRepo->getDocumentData($request);
               $docStatus =  $this->checkFileExistInS3($documentData);
               if (!$docStatus) {
                    return $this->responseJson(config('journey/http-status.failure.status'), config('journey/http-status.failure.message'), config('journey/http-status.failure.code'), []);
               }
               $docRepo->removeDocumentData($request);
               return $this->responseJson(config('journey/http-status.update.status'), "File Sucessfully Deleted", config('journey/http-status.update.code'), []);
          } catch (\Throwable $throwable) {
               Log::info("IncreaseEligibilityDocumentService -  fileRemove " . $throwable);
          }
     }


     /**
      * Check File Exist in S3
      *
      */
     public function checkFileExistInS3($documentData)
     {
          try {
               if (count($documentData)) {
                    foreach ($documentData as $value) {
                         $filePath =  'https://' . env('S3_BUCKET');
                         $file =  str_replace($filePath, "", $value['document_saved_location']);
                         if (Storage::disk('s3')->exists($file)) {
                              Storage::disk('s3')->delete($file);
                              return true;
                         } else {
                              return false;
                         }
                    }
               } else {
                    return false;
               }
          } catch (\Throwable $throwable) {
               Log::info("IncreaseEligibilityDocumentService -  checkFileExistInS3 " . $throwable);
          }
     }


     /**
      * Update Document stage
      *
      */
     public function update(Request $request, DocumentRepository $documentRepo, ImpressionRepository $impressionRepo, ApplicationRepository $applicationRepo)
     {
          $documentData = $documentRepo->view($request);
          if (count($documentData) < 1) {
               return $this->responseJson(config('journey/http-status.bad-request.status'), config('journey/http-status.bad-request.message'), config('journey/http-status.bad-request.code'), []);
          }

          $request['next_stage'] = config('constants/productStepHandle.sanction-letter');
          $request['master_product_step_id'] = $this->getCurrentStepId($request);
          // save into impression Table
          $impressionSave = $impressionRepo->save($request->all());
          if ($impressionSave->id) {
               $previousImpression = $impressionRepo->getPreviousImpressionId($impressionSave->id, $request);
               $request['previous_impression_id'] = $previousImpression->id ?? $impressionSave->id;
               $request['current_impression_id'] = $impressionSave->id;
               // save into application Table
               $applicationRepo->save($request->all());
               $logPushData = $request;
               $logPushData['cc_stage_handle'] = 'sanction';
               $logPushData['cc_sub_stage_handle'] = 'sanction-not-generated';
               $this->pushDataFieldTrackingLog($logPushData);
               return $this->responseJson(config('journey/http-status.update.status'), config('journey/http-status.update.message'), config('journey/http-status.update.code'), []);
          }
     }

     /**
      * edit Document 
      *
      */
     public function view(Request $request, DocumentRepository $increseEligibilityDocumentRepo)
     {
          try {
               $documentData = $increseEligibilityDocumentRepo->view($request);
               return $this->responseJson(config('journey/http-status.update.status'), config('journey/http-status.update.message'), config('journey/http-status.update.code'), $documentData);
          } catch (Throwable | Exception | HttpClientException $throwable) {
               Log::info("IncreaseEligibilityDocumentService -  view " . $throwable);
          }
     }

     /**
      * get document list
      *
      */
     public function documentList(Request $request, DocumentRepository $increseEligibilityDocumentRepo)
     {
          try {
               $documentData = $increseEligibilityDocumentRepo->view($request);
               return $this->responseJson(config('journey/http-status.update.status'), config('journey/http-status.update.message'), config('journey/http-status.update.code'), $documentData);
          } catch (Throwable | Exception | HttpClientException $throwable) {
               Log::info("IncreaseEligibilityDocumentService -  documentList " . $throwable);
          }
     }


     /**
      * handle preparation
      *
      * @param  $handle
      * @return mixed
      */
     public function handlePreparation($handleValue)
     {
          $handle = strtolower($handleValue);

          $handle = preg_replace('/[-\s]+/', '-', $handle);
          return $handle;
     }
     public function list(Request $request, DocumentRepository $documentRepo)
     {
          try {
               $documentList = $documentRepo->list($request);
               return $this->responseJson(
                    config('crm/http-status.success.status'),
                    config('crm/http-status.success.message'),
                    config('crm/http-status.success.code'),
                    $documentList
               );
          } catch (Throwable | HttpClientException $throwable) {
               Log::info("IncreseEligibilityDocumentService list : %s", $throwable->__toString());
          }
     }
     public function exportDocumentDetails(Request $request)
     {
          try {
               $repository = new DocumentRepository();
               $data['methodName'] = 'list';
               $data['fileName'] = 'Document-Detail-Report-';
               $data['moduleName'] = 'DocumentDetail';
               return $this->exportData($request, $repository, $data);
          } catch (Throwable | HttpClientException $throwable) {
               Log::info("IncreseEligibilityDocumentService exportDocumentDetails : %s", $throwable->__toString());
          }
     }
}
