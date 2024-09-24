<?php

namespace App\Console\Commands;

use Throwable;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use App\Utils\CoreTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Repositories\MasterRepository;
use GuzzleHttp\Exception\ClientException;
use App\Repositories\HousingJourney\MasterApiLogRepository;
use App\Repositories\HousingJourney\MasterProductRepository;
use App\Repositories\HousingJourney\MasterDocumentRepository;
use App\Repositories\HousingJourney\ProductTypeMasterRepository;

class UpsertMasterDataCommand extends Command
{
    use CoreTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'UpsertMasterData:Records';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upsert Master Data';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    private $masterRepo;
    private $masterApiLogRepo;
    private $productTypeMasterRepo;
    private $masterDocumentRepo;
    private $masterProductRepo;
    public function __construct(MasterRepository $masterRepo, MasterApiLogRepository $masterApiLogRepo, ProductTypeMasterRepository $productTypeMasterRepo, MasterDocumentRepository $masterDocumentRepo, MasterProductRepository $masterProductRepo)
    {
        $this->masterRepo = $masterRepo;
        $this->masterApiLogRepo = $masterApiLogRepo;
        $this->productTypeMasterRepo = $productTypeMasterRepo;
        $this->masterDocumentRepo = $masterDocumentRepo;
        $this->masterProductRepo = $masterProductRepo;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $this->info("Master Data Process Start...");
            Log::info("Master Data Process Start...");
            $payLoad['data'] = env('CORE_MASTER_WEB_DATA_KEY');
            $payLoad['api_url'] = env('CORE_API_URL') . 'GetWebMasterData';
            $payLoad['api_type'] =  config('constants/apiType.CORE_TO_JOURNEY');
            $payLoad['api_log_data'] =  config('constants/apiSourcePage.MASTER_DATA_IMPORT');
            $payLoad['method'] = "POST";
            $payLoad['type'] = config('constants/apiSource.CORE');
            $authToken = $this->coreAuthTokenApiCall();
            $updatedToken = str_replace('"', "", $authToken);
            $cleanedToken = str_replace('\\', '', $updatedToken);
            $apiResponse = $this->coreClientApiCall($payLoad, [
                'Content-Type' => 'application/json',
                'X-Api-Source' => $payLoad['type'],
                'X-Api-Source-Page' => $payLoad['api_log_data'],
                'X-Api-Type' => $payLoad['api_type'],
                'X-Api-Status' => config('constants/apiStatus.INIT'),
                'X-Api-Url' => $payLoad['api_url'],
                'AuthTime' => $cleanedToken
            ]);
            if ($apiResponse && $apiResponse == config('journey/http-status.timeout.message')) {
                Log::info("Connection timeout...");
            }
            $allMasterData = json_decode($apiResponse, true);
            $jsonMasterData = json_decode($allMasterData['Table'][0]['MAS_DATA']);
            if (!empty($allMasterData)) {
                foreach ($jsonMasterData as $key => $value) {
                    $key = "upsert_" . $key;
                    $methodName = Str::camel($key);
                    $this->upsertMasterRecords($value, $methodName);
                }
            } else {
                $this->info("Process Master Data is Empty...");
                Log::info("Process Master Data is Empty...");
            }
            $this->info("Master Data Process End...");
            Log::info("Master Data Process End...");
        } catch (Throwable | ClientException $e) {
            Log::info("upsertMasterDataCommand " . $e->getMessage());
        }
    }

    /**
     * upsert master records.
     *
     * @return mixed
     */
    public function upsertMasterRecords($value, $methodName)
    {
        try {
            $professionArray = array();
            $propertyCurrentStatusArray = array();
            $productCode = '';
            foreach ($value as $data) {
                if ($methodName == "upsertDocumentType") {
                    $reqData['name'] = $data->value;
                    $reqData['master_id'] = trim($data->id);
                    $reqData['max_file'] = (int)$data->max_file;
                    $reqData['max_size_per_file_mb'] = (int)$data->max_size_per_file_mb;
                    $reqData['allowed_extensions'] = $data->allowed_extensions;
                    $reqData['handle'] = $this->handlePreparation($reqData['name']);
                    $reqData['is_active'] = $data->is_active == 1 ? true : false;
                    $this->masterRepo->$methodName($reqData);

                    $masterReqData['name']  = $data->GrpCd;
                    $masterReqData['handle'] = $this->handlePreparation($masterReqData['name']);
                    $masterReqData['is_active'] = $data->is_active == 1 ? true : false;
                    $this->masterRepo->upsertMasterDocumentType($masterReqData);

                    $mappingDocData['master_document_type_id']
                        = $this->masterDocumentRepo->getMasterDocumentTypeId($data->GrpCd);
                    $mappingDocData['master_document_id']
                        =  $this->masterDocumentRepo->getMasterDocumentId($data->value);
                    $this->masterRepo->upsertMappingDocumentType($mappingDocData);
                } elseif ($methodName == "upsertProductsDetails") {
                    $prod['name'] = $data->value;
                    $prod['product_id'] = trim($data->id);
                    $prod['display_name'] = config('constants/productName.' . $data->PrdCd);
                    $prod['code'] = $data->PrdCd;
                    $prod['handle'] = $this->handlePreparation($prod['name']);
                    $prod['processing_fee'] = config('constants/processingFee.PROCESSING_FEE');
                    $prod['is_active'] = $data->is_active;
                    $this->masterRepo->$methodName($prod);
                    $prodType['name'] = $data->GrpNm;
                    $prodType['code'] = $data->GrpCd;
                    $prodType['handle'] = $this->handlePreparation($prodType['name']);
                    $prodType['is_active'] = $data->is_active;
                    $this->masterRepo->upsertMasterProductType($prodType);
                    $updateData['master_product_id'] = $this->masterProductRepo->getProductId($data->PrdCd);
                    $updateData['master_product_type_id'] = $this->productTypeMasterRepo->view($data->GrpNm);
                    $this->productTypeMasterRepo->upsertProductTypeMasterMapping($updateData);
                } elseif ($methodName == 'upsertEmpConstitueType') {
                    $constitutionValue =
                        config('constants/employmentConstitutionType.' . $this->handlePreparation($data->value));
                    $masterData['name'] = $data->value;
                    $masterData['master_id'] = trim($data->id);
                    $masterData['handle'] = $this->handlePreparation($data->value);
                    if (count($constitutionValue) > 0) {
                        $masterData['display_name'] = $constitutionValue['display_name'];
                        $masterData['order_id'] = $constitutionValue['order_id'];
                        $masterData['is_active'] = $constitutionValue['is_active'];
                        $this->masterRepo->$methodName($masterData);
                    }
                } else {
                    if ($methodName == "upsertPropertyCurrentSts") {
                        $masterData['display_name'] =  config('constants/propertyCurrentStatus.' .  $data->value);
                        array_push($propertyCurrentStatusArray, $data->value);
                    }
                    if ($methodName == 'upsertProfessionalType') {
                        array_push($professionArray, $data->value);
                    }
                    $masterData['name'] = $data->value;
                    $masterData['master_id'] = trim($data->id);
                    $masterData['handle'] = $this->handlePreparation($data->value);
                    $masterData['is_active'] = $data->is_active;
                    if ($methodName == 'upsertPropertyType') {
                        if ($masterData['handle'] == 'residential') {
                            $productCode  = 'RP';
                        } elseif ($masterData['handle'] == 'commercial') {
                            $productCode  = 'CP';
                        } elseif ($masterData['handle'] == 'industrial') {
                            $productCode  = 'IP';
                        }
                        $masterData['product_code'] =  $productCode;
                    }
                    $this->masterRepo->$methodName($masterData);
                }
            }
            if ($methodName == 'upsertProfessionalType') {
                $existData = $this->masterRepo->getExistingProfType($professionArray);
                $this->masterRepo->removeExistProfType($existData);
            }
            if ($methodName == 'upsertPropertyCurrentSts') {
                $existData = $this->masterRepo->getExistingPropCurState($propertyCurrentStatusArray);
                $this->masterRepo->removeExistPropCurState($existData);
            }
        } catch (Throwable | ClientException $e) {
            Log::info("upsertMasterRecords " . $e->getMessage());
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
        if (preg_replace('/\*-\+\*-*/', ' ', $handle)) {
            $handle = preg_replace('/[+\/]/', '', $handle);
        }
        $handle = preg_replace('/[-\s]+/', '-', $handle);
        return $handle;
    }

    public function getSourceMethod($apiUrl)
    {
        $url = $apiUrl;
        $values = parse_url($url);
        $path = explode('/', $values['path']);
        $getPath = Arr::last($path);
        $getStr = str_replace('-', '_', $getPath);
        return strtoupper($getStr);
    }
}
