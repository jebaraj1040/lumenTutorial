<?php

namespace App\Services;

use Exception;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Client\HttpClientException;
use App\Repositories\DebugRepository;
use App\Utils\CoreTrait;
use App\Repositories\HousingJourney\AddressRepository;
use App\Repositories\HousingJourney\ApplicationRepository;
use App\Repositories\HousingJourney\FieldTrackingRepository;
use App\Repositories\HousingJourney\ImpressionRepository;
use App\Repositories\HousingJourney\MasterProductRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class DebugService extends Service
{
    /**
     * search pincode
     *
     * @param  Request $request
     *
     */
    use CoreTrait;
    public function searchPinCode(Request $request, DebugRepository $debugRepo)
    {
        try {
            $pincodeData = $debugRepo->pincodeSearch($request['pincode']);
            return $this->responseJson(
                config('journey/http-status.success.status'),
                config('journey/http-status.success.message'),
                config('journey/http-status.success.code'),
                $pincodeData
            );
        } catch (Throwable | Exception | HttpClientException $throwable) {
            throw new Exception(
                sprintf("Service : DebugService , Method : searchPinCode : %s", $throwable->__toString())
            );
        }
    }
    /**
     * search industry
     *
     * @param  Request $request
     *
     */
    public function searchIndustry(Request $request, DebugRepository $debugRepo)
    {
        try {
            $industryData = $debugRepo->industrySearch($request['industry_type']);
            return $this->responseJson(
                config('journey/http-status.success.status'),
                config('journey/http-status.success.message'),
                config('journey/http-status.success.code'),
                $industryData
            );
        } catch (Throwable | Exception | HttpClientException $throwable) {
            throw new Exception(
                sprintf("Service : DebugService , Method : searchIndustry : %s", $throwable->__toString())
            );
        }
    }
    /**
     * search company
     *
     * @param  Request $request
     *
     */
    public function searchCompany(Request $request, DebugRepository $debugRepo)
    {
        try {
            $companyData = $debugRepo->companySearch($request['company_name']);
            return $this->responseJson(
                config('journey/http-status.success.status'),
                config('journey/http-status.success.message'),
                config('journey/http-status.success.code'),
                $companyData
            );
        } catch (Throwable | Exception | HttpClientException $throwable) {
            throw new Exception(
                sprintf("Service : DebugService , Method : searchCompany : %s", $throwable->__toString())
            );
        }
    }
    /**
     * search project
     *
     * @param  Request $request
     *
     */
    public function searchProject(Request $request, DebugRepository $debugRepo)
    {
        try {
            $projectName = str_replace('%20', ' ', $request['project_name']);
            $projectData = $debugRepo->projectSearch($projectName);
            return $this->responseJson(
                config('journey/http-status.success.status'),
                config('journey/http-status.success.message'),
                config('journey/http-status.success.code'),
                $projectData
            );
        } catch (Throwable | Exception | HttpClientException $throwable) {
            throw new Exception(
                sprintf("Service : DebugService , Method : searchProject : %s", $throwable->__toString())
            );
        }
    }
    /**
     * search ifsc
     *
     * @param  Request $request
     *
     */
    public function searchIfsc(Request $request, DebugRepository $debugRepo)
    {
        try {
            $ifscData = $debugRepo->ifscSearch($request['ifsc']);
            return $this->responseJson(
                config('journey/http-status.success.status'),
                config('journey/http-status.success.message'),
                config('journey/http-status.success.code'),
                $ifscData
            );
        } catch (Throwable | Exception | HttpClientException $throwable) {
            throw new Exception(
                sprintf("Service : DebugService , Method : searchIfsc : %s", $throwable->__toString())
            );
        }
    }
    /**
     * search state
     *
     * @param  Request $request
     *
     */
    public function searchState(Request $request, DebugRepository $debugRepo)
    {
        try {
            $stateName = str_replace("%20", " ", $request['state']);
            $stateData = $debugRepo->stateSearch($stateName);
            return $this->responseJson(
                config('journey/http-status.success.status'),
                config('journey/http-status.success.message'),
                config('journey/http-status.success.code'),
                $stateData
            );
        } catch (Throwable | Exception | HttpClientException $throwable) {
            throw new Exception(
                sprintf("Service : DebugService , Method : searchState : %s", $throwable->__toString())
            );
        }
    }
    /**
     * search employment type
     *
     * @param  Request $request
     *
     */
    public function searchEmploymentType(Request $request, DebugRepository $debugRepo)
    {
        try {
            $employmentType = str_replace("%20", " ", $request['employment_type']);
            $employmentData = $debugRepo->employmentType($employmentType);
            return $this->responseJson(
                config('journey/http-status.success.status'),
                config('journey/http-status.success.message'),
                config('journey/http-status.success.code'),
                $employmentData
            );
        } catch (Throwable | Exception | HttpClientException $throwable) {
            throw new Exception(
                sprintf("Service : DebugService , Method : searchEmploymentType : %s", $throwable->__toString())
            );
        }
    }
    /**
     * get bre data
     *
     * @param  Request $request
     *
     */
    public function getBreData(Request $request)
    {
        try {
            unset($request['lead_id']);
            unset($request['quote_id']);
            $aesEncryption = $this->aesEncryption($request->all());
            $payLoad['data'] = $aesEncryption;
            $payLoad['api_url'] = env('CORE_API_URL') . 'ProcessBRE';
            $payLoad['api_type'] =  $request->header('X-Api-Type');
            $payLoad['method'] = "POST";
            $payLoad['type'] = $request->header('X-Api-Source');
            $authToken = $this->coreAuthTokenApiCall();
            $updatedToken = str_replace('"', "", $authToken);
            $cleanedToken = str_replace('\\', '', $updatedToken);
            $apiResponse = $this->coreClientApiCall($payLoad, [
                'Content-Type' => 'application/json',
                'X-Api-Source' => $request->header('X-Api-Source'),
                'X-Api-Type' => $request->header('X-Api-Type'),
                'X-Api-Source-Page' => $request->header('X-Api-Source-Page'),
                'X-Api-Url' => $payLoad['api_url'],
                'AuthTime' => $cleanedToken
            ]);
            if ($apiResponse) {
                if ($apiResponse == 'Connection timeout' || $apiResponse == 'Error:Contact Administator') {
                    return $this->responseJson(
                        config('journey/http-status.timeout.status'),
                        config('journey/http-status.timeout.message'),
                        config('journey/http-status.timeout.code'),
                        $apiResponse
                    );
                }
                return $this->responseJson(
                    config('journey/http-status.success.status'),
                    config('journey/http-status.success.message'),
                    config('journey/http-status.success.code'),
                    json_decode($apiResponse)
                );
            } else {
                return $this->responseJson(
                    config('journey/http-status.error.status'),
                    config('journey/http-status.error.message'),
                    config('journey/http-status.error.code'),
                    []
                );
            }
        } catch (Throwable | Exception | HttpClientException $throwable) {
            throw new Exception(
                sprintf("Service : DebugService , Method : getBreData : %s", $throwable->__toString())
            );
        }
    }
    /**
     * get customer data
     *
     * @param  Request $request
     *
     */
    public function getCustomerData(Request $request)
    {
        try {
            $customerData = $this->customerFetchApiForPanHistory(
                $request->all(),
                $request->header('X-Api-Source-Page')
            );
            if (count($customerData['Table']) > 0) {
                return $this->responseJson(
                    config('journey/http-status.success.status'),
                    config('journey/http-status.success.message'),
                    config('journey/http-status.success.code'),
                    $customerData
                );
            } else {
                return $this->responseJson(
                    config('journey/http-status.success.status'),
                    "No data found",
                    config('journey/http-status.success.code'),
                    []
                );
            }
        } catch (Throwable | Exception | HttpClientException $throwable) {
            throw new Exception(
                sprintf("Service : DebugService , Method : getCustomerData : %s", $throwable->__toString())
            );
        }
    }
    /**
     * get karza data
     *
     * @param  Request $request
     *
     */
    public function getKarzaData(Request $request, DebugRepository $debugRepo)
    {
        try {
            $viewKarzaHistroy = $debugRepo->viewKarzaHistroy(strtoupper($request['pan']));
            if ($viewKarzaHistroy) {
                $currentDate = Carbon::now()->format('Y-m-d');
                $carbonDate = Carbon::createFromTimestampMs($viewKarzaHistroy->created_at);
                $createdDate = $carbonDate->format('Y-m-d');
                $to = Carbon::parse($currentDate);
                $from = Carbon::parse($createdDate);
                $days = $to->diffInDays($from);
                if ($days < 30) {
                    return $this->responseJson(
                        config('journey/http-status.success.status'),
                        config('journey/http-status.success.message'),
                        config('journey/http-status.success.code'),
                        $viewKarzaHistroy->api_data
                    );
                } else {
                    $karzaData = $this->fetchAddressFromKarza($request->all());
                }
            } else {
                $karzaData = $this->fetchAddressFromKarza($request->all());
            }
            $decodedAddress  =  json_decode($karzaData, true);
            $karzaLog['lead_id'] = null;
            $karzaLog['quote_id'] = null;
            $karzaLog['pan'] = strtoupper($request['pan']);
            $karzaLog['master_product_id'] = null;
            $karzaLog['api_source'] = config('constants/apiSource.CORE');
            $karzaLog['api_source_page'] = $request->header('X-Api-Source-Page') ?  $request->header('X-Api-Source-Page') : config('constants/apiSourcePage.DEBUG_SERVICE');
            $karzaLog['api_type'] = config('constants/apiType.KARZA_PAN_DATA');
            $karzaLog['api_header'] = $request['header'] ?? null;
            $karzaLog['api_url'] = env('CORE_API_URL') . 'karzaPan';
            $karzaLog['api_request_type'] = config('constants/apiType.RESPONSE');
            $karzaLog['api_data'] = $decodedAddress;
            $karzaLog['api_status_code'] =
                isset($decodedAddress['panNo']) ? config('journey/http-status.success.code') : 402;
            $karzaLog['api_status_message'] =  isset($decodedAddress['panNo']) ? config('journey/http-status.success.message') : config('journey/http-status.error.message');
            $addressRepo = new AddressRepository();
            $addressRepo->saveKarzaHistroy($karzaLog);
            return $this->responseJson(
                config('journey/http-status.success.status'),
                config('journey/http-status.success.message'),
                config('journey/http-status.success.code'),
                $decodedAddress

            );
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("DebugService -  getKarzaData " . $throwable);
        }
    }
    /**
     * get karza data
     *
     * @param  Request $request
     *
     */
    public function fetchCibilData(Request $request)
    {
        try {
            $cibilData = $this->getCibilData($request->all());
            if ($cibilData) {
                return $this->responseJson(
                    config('journey/http-status.success.status'),
                    config('journey/http-status.success.message'),
                    config('journey/http-status.success.code'),
                    $cibilData
                );
            } else {
                return $this->responseJson(
                    config('journey/http-status.success.status'),
                    "No data found",
                    config('journey/http-status.success.code'),
                    []
                );
            }
        } catch (Throwable | Exception | HttpClientException $throwable) {
            throw new Exception(
                sprintf("Service : DebugService , Method : getCibilData : %s", $throwable->__toString())
            );
        }
    }
    /**
     * get final submit data
     *
     * @param  Request $request
     *
     */
    public function getFinalSumbitData(Request $request)
    {
        try {
            $requestEncrypt = $this->aesEncryption($request->all());
            $payLoad['data'] = $requestEncrypt;
            $payLoad['api_url'] = env('CORE_API_URL') . 'WebSiteGpLead';
            $payLoad['api_type'] =  config('constants/apiType.FINAL_SUBMIT');
            $payLoad['method'] = "POST";
            $payLoad['type'] = $request->header('X-Api-Source');
            $authToken = $this->coreAuthTokenApiCall();
            $updatedToken = str_replace('"', "", $authToken);
            $cleanedToken = str_replace('\\', '', $updatedToken);
            $apiResponse = $this->coreClientApiCall($payLoad, [
                'Content-Type' => 'application/json',
                'X-Api-Source' => $payLoad['type'],
                'X-Api-Type' => $payLoad['api_type'],
                'X-Api-Source-Page' => config('constants/apiSourcePage.SANCTION_LETTER_DOWNLOAD'),
                'X-Api-Url' => $payLoad['api_url'],
                'AuthTime' => $cleanedToken
            ]);
            if ($apiResponse) {
                return $this->responseJson(
                    config('journey/http-status.success.status'),
                    config('journey/http-status.success.message'),
                    config('journey/http-status.success.code'),
                    json_decode($apiResponse)
                );
            } else {
                return $this->responseJson(
                    config('journey/http-status.error.status'),
                    config('journey/http-status.error.message'),
                    config('journey/http-status.error.code'),
                    []
                );
            }
        } catch (Throwable | Exception | HttpClientException $throwable) {
            throw new Exception(
                sprintf("Service : DebugService , Method : getFinalSubmitData : %s", $throwable->__toString())
            );
        }
    }
    /**
     * get partner fetch data
     *
     * @param  Request $request
     *
     */
    public function fetchPartnerData(Request $request)
    {
        try {
            $partnerData = $this->partnerFetchApi($request->all());
            if ($partnerData && $partnerData == config('journey/http-status.timeout.message')) {
                return $this->responseJson(
                    config('journey/http-status.timeout.status'),
                    config('journey/http-status.timeout.message'),
                    config('journey/http-status.timeout.code')
                );
            } else {
                return $this->responseJson(
                    config('journey/http-status.error.status'),
                    config('journey/http-status.error.message'),
                    config('journey/http-status.error.code'),
                    $partnerData
                );
            }
        } catch (Throwable | Exception | HttpClientException $throwable) {
            throw new Exception(
                sprintf("Service : DebugService , Method : getFinalSubmitData : %s", $throwable->__toString())
            );
        }
    }

    /**
     * Read Lumen Error Log Method
     *
     * @param  Request $request
     * @return array
     */
    public function readErrorLog(Request $request): string
    {
        if (empty($request->logDate) === false) {
            $errorPath = sprintf("%slumen-%s.log", storage_path('logs/'), $request->logDate);
            $getErrorLog = `tail -n 100 $errorPath `;
            if (!$getErrorLog) {
                return sprintf("No logdata found in this date %s", $request->logDate);
            }
            return $getErrorLog;
        }
        $errorPath = storage_path('logs/lumen.log');
        $getErrorLog = `tail -n 100 $errorPath `;
        if (!$getErrorLog) {
            return "No logdata found";
        }
        return $getErrorLog;
    }

    /**
     * Property Mapping
     *
     * @param  Request $request
     */
    public function propertyDetailsMapping(Request $request)
    {
        $updatedProductCode =  $this->checkPropertyConvertionStatus($request->all());
        $proRepo = new MasterProductRepository();
        $existProductData = $proRepo->masterProductDataFetch($request->product_code);
        $resData['existing_product_code'] = $existProductData['name'] . " ( " . $request->product_code . " )";
        $resData['converted_product_code'] = $existProductData['name'] . " ( " . $request->product_code . " )";
        if ($updatedProductCode) {
            $updatedProductData = $proRepo->masterProductDataFetch($updatedProductCode);
            $resData['converted_product_code'] = $updatedProductData['name'] . " ( " . $updatedProductData['code'] . " )";
        }

        return $this->responseJson(
            config('journey/http-status.success.status'),
            config('journey/http-status.success.message'),
            config('journey/http-status.success.code'),
            $resData
        );
    }

    /**
     * check property convertion status
     *
     * @param $request
     */
    public function checkPropertyConvertionStatus($request)
    {
        try {
            $proRepo = new MasterProductRepository();
            $masterProductCode = $request['product_code'];
            $proHandle = $request['property_type'];
            $loanFree = $request['loan_on_this_property'];
            $existingCustomer = false; // New customer : false existing Customer : true
            $propertySelected = false; // Selected : true or false
            $breOneUpdatedLoanAmount = $request['bre1_loan_amount'];
            $outstandingLoanAmount = $request['outstanding_loan_amount'];
            switch ($masterProductCode) {
                    //HLBT
                case config('constants/productCode.HLBT'):
                    if ($loanFree === true &&  $proHandle == "1" && $breOneUpdatedLoanAmount > $outstandingLoanAmount) {
                        return config('constants/productCode.HLBTTopup');
                    } elseif ($loanFree === true &&  ($proHandle == "3" || $proHandle == "2") && $breOneUpdatedLoanAmount > $outstandingLoanAmount) {
                        return config('constants/productCode.LAPBTTopup');
                    } elseif ($loanFree === true && ($proHandle == "3" || $proHandle == "2") && $breOneUpdatedLoanAmount <= $outstandingLoanAmount) {
                        return config('constants/productCode.LAPBT');
                    } elseif ($loanFree === false &&  ($proHandle == "3" || $proHandle == "2")) {
                        //SheetID : 6
                        return config('constants/productCode.LAPBT');
                    }
                    break;
                    //HLNew
                case config('constants/productCode.HLNew'):
                    if ($request['property_purchase_from'] == "Seller") {
                        return config('constants/productCode.HLResale');
                    }
                    break;

                    // Home extension
                case config('constants/productCode.HLExt'):

                    if ($loanFree === false || ($existingCustomer === true && $propertySelected === true)) {
                        return config('constants/productCode.HLExt');
                    } elseif ($loanFree === true) {
                        return config('constants/productCode.HLBTExt');
                    }
                    break;

                    // Home improvement
                case config('constants/productCode.HLImp'):

                    if ($loanFree === false || ($existingCustomer === true && $propertySelected === true)) {
                        return config('constants/productCode.HLImp');
                    } elseif ($loanFree === true) {
                        return config('constants/productCode.HLBTImp');
                    }
                    break;

                    //LAPCom
                case config('constants/productCode.LAPCom'):

                    if (($proHandle == "3" || $proHandle == "2") && $loanFree === false) {
                        return config('constants/productCode.LAPCom');
                    } elseif (($proHandle == "3" || $proHandle == "2") && $loanFree === true && $breOneUpdatedLoanAmount <= $outstandingLoanAmount) {
                        return config('constants/productCode.LAPBT');
                    } elseif (($proHandle == "3" || $proHandle == "2") && $loanFree === true && $breOneUpdatedLoanAmount > $outstandingLoanAmount) {
                        return config('constants/productCode.LAPBTTopup');
                    } elseif ($proHandle == "1"  && $loanFree === false) {
                        //SheetID : 11
                        return config('constants/productCode.LAPResi');
                    } elseif ($proHandle == "1" && $loanFree === true && $breOneUpdatedLoanAmount > $outstandingLoanAmount) {
                        //SheetID : 13
                        return config('constants/productCode.LAPBTTopup');
                    } elseif ($proHandle == "1" && $loanFree === true && $breOneUpdatedLoanAmount < $outstandingLoanAmount) {
                        //SheetID : 14
                        return config('constants/productCode.LAPBT');
                    }
                    break;

                    //LAPResi
                case config('constants/productCode.LAPResi'):

                    if ($proHandle == "1"   && $loanFree === false) {
                        return config('constants/productCode.LAPResi');
                    } elseif ($proHandle == "1" && $loanFree === true && $breOneUpdatedLoanAmount <= $outstandingLoanAmount) {
                        return config('constants/productCode.LAPBT');
                    } elseif ($proHandle == "1" && $loanFree === true && $breOneUpdatedLoanAmount > $outstandingLoanAmount) {
                        return config('constants/productCode.LAPBTTopup');
                    } elseif (($proHandle == "2" || $proHandle == "3") && $loanFree === false) {
                        //SheetID : 18
                        return config('constants/productCode.LAPCom');
                    } elseif (($proHandle == "2" || $proHandle == "3") && $loanFree === true && $breOneUpdatedLoanAmount > $outstandingLoanAmount) {
                        //SheetID : 21 
                        return config('constants/productCode.LAPBTTopup');
                    } elseif (($proHandle == "2" || $proHandle == "3") && $loanFree === true && $breOneUpdatedLoanAmount < $outstandingLoanAmount) {
                        //SheetID : 22 
                        return config('constants/productCode.LAPBT');
                    }
                    break;

                    //   LAPTopup or HLTopup
                case config('constants/productCode.HLTopup'):
                case config('constants/productCode.LAPTopup'):
                    if ($existingCustomer === false && $proHandle == "1" && $loanFree === false) {
                        return config('constants/productCode.LAPResi');
                    } elseif ($existingCustomer === false && $proHandle == "1" && $loanFree === true && $breOneUpdatedLoanAmount > $outstandingLoanAmount) {
                        //SheetID : 31
                        return config('constants/productCode.HLBTTopup');
                    } elseif ($existingCustomer === false && $proHandle == "1" && $loanFree === true && $breOneUpdatedLoanAmount < $outstandingLoanAmount) {
                        //SheetID : 32
                        return config('constants/productCode.HLBT');
                    } elseif ($existingCustomer === false && ($proHandle == "2" || $proHandle == "3") && $loanFree === false) {
                        return config('constants/productCode.LAPTopup');
                    } elseif ($existingCustomer === false && ($proHandle == "2" || $proHandle == "3") && $loanFree === true && $breOneUpdatedLoanAmount > $outstandingLoanAmount) {
                        //SheetID : 33
                        return config('constants/productCode.LAPBTTopup');
                    } elseif ($existingCustomer === false && ($proHandle == "2" || $proHandle == "3") && $loanFree === true && $breOneUpdatedLoanAmount < $outstandingLoanAmount) {
                        //SheetID : 34
                        return config('constants/productCode.LAPBT');
                    } elseif ($existingCustomer === true && $propertySelected === true && ($proHandle == "2" || $proHandle == "3")) {
                        return config('constants/productCode.LAPTopup');
                    } elseif ($existingCustomer === true && $propertySelected === true &&  $proHandle == "1") {
                        return config('constants/productCode.HLTopup');
                    }
                    break;
            }
        } catch (Throwable | Exception | HttpClientException $throwable) {
            throw new Exception(
                sprintf("Service : DebugService , Method : save : %s", $throwable->__toString())
            );
        }
    }
    /**
     * update application updated date
     *
     * @param $request
     */
    public function applicationDateUpdate(Request $request, DebugRepository $debugRepo)
    {
        try {
            $applicationUpdate = $debugRepo->updateApplicationDate($request);
            if ($applicationUpdate) {
                return $this->responseJson(
                    config('journey/http-status.success.status'),
                    config('journey/http-status.success.message'),
                    config('journey/http-status.success.code'),
                    []
                );
            } else {
                return $this->responseJson(
                    config('journey/http-status.failure.status'),
                    config('journey/http-status.failure.message'),
                    config('journey/http-status.failure.code'),
                    []
                );
            }
        } catch (Throwable | Exception | HttpClientException $throwable) {
            throw new Exception(
                sprintf("Service : DebugService , Method : save : %s", $throwable->__toString())
            );
        }
    }

    public function createQuoteID()
    {
        $applicationRepo = new ApplicationRepository();
        $randomNumber = random_int(100000, 999999);
        $length = 6;
        $randomString = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 1, $length);
        $quoteId =  $randomString . $randomNumber;
        $checkQuote = $applicationRepo->getApplication($quoteId);
        if (empty($checkQuote) === false) {
            $this->createQuoteID();
        }
        return $quoteId;
    }

    /**
     * update cc push tag
     *
     * @param $request
     */
    public function ccPushTagUpdate(Request $request)
    {
        try {
            $fieldRepo = new FieldTrackingRepository();
            $recentFieldData = $fieldRepo->ccPushTagUpdate($request->quote_id);
            if ($recentFieldData) {
                return $this->responseJson(
                    config('journey/http-status.success.status'),
                    config('journey/http-status.success.message'),
                    config('journey/http-status.success.code'),
                    []
                );
            } else {
                return $this->responseJson(
                    config('journey/http-status.failure.status'),
                    config('journey/http-status.failure.message'),
                    config('journey/http-status.failure.code'),
                    []
                );
            }
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("DebugService ccPushTagUpdate" . $throwable->__toString());
        }
    }
}
