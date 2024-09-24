<?php

namespace App\Services\HousingJourney;

use App\Repositories\HousingJourney\MasterApiLogRepository;
use Illuminate\Http\Request;
use GuzzleHttp\Exception\ClientException;
use App\Utils\CrmTrait;
use Illuminate\Support\Facades\Log;
use App\Services\Service;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Client\HttpClientException;
use Throwable;
use Exception;
use App\Repositories\HousingJourney\MasterProductRepository;
use App\Jobs\MasterProductExport;

class MasterProductService extends Service
{
     use CrmTrait;
     private $masterproductRepo;
     /**
      * insert value into product master table.
      *@param $request
      */
     public function __construct(MasterProductRepository $masterproductRepo)
     {
          $this->masterproductRepo = $masterproductRepo;
     }
     /**
      * save data to masterproduct table
      *@param $request
      */
     public function saveProduct(
          Request $request,
          MasterApiLogRepository $masterApiLogRepo,
          MasterProductRepository $productMasterRepo
     ) {
          try {
               $rules = [
                    'name' => 'required',
                    'code' => 'required',
                    'fee' => 'required',
               ];
               $validator = Validator::make($request->all(), $rules);
               if ($validator->fails()) {
                    return $validator->errors();
               }
               $requestUrl = $request->url . $request->path();
               $requestData['customHeader']['X-Api-Source'] = config('constants/masterApiSource.CORE');
               $requestData['customHeader']['X-Api-Type'] = config('constants/masterApiType.PRODUCT_UPSERT');
               $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.INIT');
               $requestData['customHeader']['X-Api-Url'] = $requestUrl;
               $requestData['request'] = $request;
               $masterApiLogData = $masterApiLogRepo->save($requestData);
               $request['handle'] = strtolower(str_replace(' ', '-', $request->name));
               $prodcutSave = $productMasterRepo->save($request->all());
               if ($prodcutSave) {
                    $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.SUCCESS');
                    $response = $this->responseJson(
                         config('journey/http-status.success.status'),
                         config('journey/http-status.success.message'),
                         config('journey/http-status.success.code'),
                         []
                    );
                    $masterApiLogRepo->update(
                         $masterApiLogData['id'],
                         json_encode($response),
                         $requestData['customHeader']['X-Api-Status']
                    );
                    return $response;
               } else {
                    $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.FAILURE');
                    $response = $this->responseJson(
                         config('journey/http-status.failure.status'),
                         config('journey/http-status.failure.message'),
                         config('journey/http-status.failure.code'),
                         []
                    );
                    $masterApiLogRepo->update(
                         $masterApiLogData['id'],
                         json_encode($response),
                         $requestData['customHeader']['X-Api-Status']
                    );
                    return $response;
               }
          } catch (Throwable | HttpClientException $throwable) {
               $requestData['request'] = $request;
               $requestData['customHeader']['X-Api-Status'] = config('constants/masterApiStatus.FAILURE');
               $masterApiLogRepo->save($requestData);
               throw new Throwable(sprintf("Service : MasterProductService,
               Method : save : %s", $throwable->__toString()));
          }
     }
     /**
      * Edit Product in Table
      *@param $request
      */
     public function editProduct(Request $request)
     {
          try {
               $productId = $request->product_id ?? null;
               $productData = $this->masterproductRepo->edit($productId);
               $productData['msg'] = config('crm/http-status.success.message');
               return  $this->successResponse($productData);
          } catch (Throwable  | ClientException $throwable) {
               throw new Throwable(
                    Log::info("Service : MenuService , Method : editProduct : %s" . $throwable->__toString())
               );
          }
     }
     /**
      * List Master Products
      *@param  $request
      */
     public function getMasterproducts(
          Request $request,
          MasterApiLogRepository $masterApiLogRepo,
          MasterProductRepository $productMasterRepo
     ) {
          try {
               $productMenu = $this->masterproductRepo->list($request);
               $productList['mainMenu'] = $productMenu;
               $productList['msg'] = config('crm/http-status.success.message');
               return  $this->successResponse($productList);
          } catch (Throwable  | ClientException $throwable) {
               throw new Throwable(
                    Log::info("Service : MenuService , Method : getMenu : %s" . $throwable->__toString())
               );
          }
     }
     /**
      * Delete  Menu.
      *
      * @param
      * @return void
      */
     public function deleteProduct(Request $request)
     {
          try {
               $deleteProduct = $this->masterproductRepo->delete($request);
               if ($deleteProduct) {
                    return $this->responseJson(
                         config('crm/http-status.delete.status'),
                         config('crm/http-status.delete.message'),
                         config('crm/http-status.delete.code')
                    );
               } else {
                    return $this->responseJson(
                         config('crm/http-status.error.status'),
                         config('crm/http-status.error.message'),
                         config('crm/http-status.error.code'),
                         []
                    );
               }
          } catch (Throwable  | ClientException $throwable) {
               throw new Throwable(Log::info("Service : MasterProductService , Method : deleteProduct : %s"
                    . $throwable->__toString()));
          }
     }
     /**
      * Exports data
      * @param $request
      */
     /**
      * get  Master Products and master products step names
      *
      */
     public function filter()
     {
          try {
               $selectFilter = $this->masterproductRepo->filter();
               return $this->responseJson(
                    config('crm/http-status.success.status'),
                    config('crm/http-status.success.message'),
                    config('crm/http-status.success.code'),
                    $selectFilter
               );
          } catch (Throwable  | ClientException $throwable) {
               Log::info("MasterProductService -  filter " . $throwable);
               return $this->responseJson(
                    config('journey/http-status.error.status'),
                    config('journey/http-status.error.message'),
                    config('journey/http-status.error.code'),
                    []
               );
          }
     }
}
