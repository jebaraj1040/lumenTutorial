<?php

namespace App\Utils;

use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Http\Client\HttpClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use App\Repositories\ApiLogRepository;
use App\Repositories\HousingJourney\PaymentLogRepository;
use App\Repositories\HousingJourney\ApplicationRepository;
use App\Repositories\HousingJourney\PaymentTransactionRepository;
use Throwable;
use App\Entities\HousingJourney\HjLead;
use App\Utils\CoreTrait;
use GuzzleHttp\Exception\ClientException;

define('CURRENT_DATE', date('Y-m-d H:i:s'));
define('JOURNEY_API_STATUS_ERROR_CODE',  config('journey/http-status.error.code'));
define('JOURNEY_API_TYPE_REQUEST', config('constants/apiType.REQUEST'));
define('JOURNEY_API_STATUS_ERROR_MESSAGE',  config('journey/http-status.error.message'));
define('JOURNEY_API_STATUS_ERROR',  config('journey/http-status.error.status'));
trait JourneyTrait
{
	/**
	 * define the guzzleApiCall method
	 *
	 * @param  $apiLogRepo
	 * @return mixed
	 */
	use CoreTrait;
	public function __construct(PaymentLogRepository $paymentLogRepo, PaymentTransactionRepository $paymentRepo)
	{
		$this->paymentLogRepo = $paymentLogRepo;
		$this->paymentRepo = $paymentRepo;
	}

	public function clientApiCall($payLoad, $headers = [])
	{
		$apiLogRepo = new ApiLogRepository();
		try {
			return $this->guzzleApiCall($payLoad, $headers);
		} catch (HttpClientException | ServerException | ClientException $e) {
			Log::info("Api__Exceptions clientApiCall " . $payLoad['api_url'] . " " . $e . "\n");
			$responseData['code'] = JOURNEY_API_STATUS_ERROR_CODE;
			$responseData['status'] = JOURNEY_API_STATUS_ERROR;
			$responseData['message'] = JOURNEY_API_STATUS_ERROR_MESSAGE;

			// prepare log data.
			$logData['api_source'] = $headers['X-Api-Source'];
			$logData['api_source_page'] = $headers['X-Api-Source-Page'];
			$logData['api_type'] = $headers['X-Api-Type'];
			$logData['api_header'] = $headers;
			$logData['api_url'] = $payLoad['api_url'];
			$logData['api_request_type'] = JOURNEY_API_TYPE_REQUEST;
			$logData['api_data'] = $payLoad;
			$logData['api_status_code'] = JOURNEY_API_STATUS_ERROR_CODE;
			$logData['api_status_message'] = JOURNEY_API_STATUS_ERROR_MESSAGE;
			if ($headers['X-Api-Type'] == "PAYMENT_INIT") {
				$logData = $this->preparePaymentLogData($logData, $payLoad);
				// Save payment log
				$this->paymentLogRepo->save($logData['logData']);
			} else {
				$apiLogRepo->save($logData);
			}
			return $responseData;
		} catch (RequestException $e) {
			// Handle the exception
			if ($e->hasResponse()) {
				$statusCode = $e->getResponse()->getStatusCode();
				$errorBody = $e->getResponse()->getBody()->getContents();
				// Log or display the error
				$responseData['code'] = $statusCode;
				$responseData['status'] = config('journey/http-status.bad-request');
				$responseData['message'] = $errorBody;
				// prepare log data.
				$logData['api_source'] = $headers['X-Api-Source'];
				$logData['api_source_page'] = $headers['X-Api-Source-Page'];
				$logData['api_type'] = $headers['X-Api-Type'];
				$logData['api_header'] = $headers;
				$logData['api_url'] = $payLoad['api_url'];
				$logData['api_request_type'] = JOURNEY_API_TYPE_REQUEST;
				$logData['api_data'] = $payLoad;
				$logData['api_status_code'] = $statusCode;
				$logData['api_status_message'] = $errorBody;
				$apiLogRepo->save($logData);
				Log::error('Request failed with status: ' . $statusCode, ['error' => $errorBody]);
			} else {
				// Handle request-level errors (e.g., network issues)
				Log::error('Request failed: ' . $e->getMessage());
			}
		} catch (ConnectException $e) {
			Log::info("connection__Exceptions " . $headers['X-Api-Type'] . " " . $e . "\n");
			$responseData['code'] = config('journey/http-status.timeout.code');
			$responseData['status'] = config('journey/http-status.timeout.status');
			$responseData['message'] = trans('http-status.timeout.message');
			// prepare log data.
			$logData['api_source'] = $headers['X-Api-Source'];
			$logData['api_source_page'] = $headers['X-Api-Source-Page'];
			$logData['api_type'] = $headers['X-Api-Type'];
			$logData['api_header'] = $headers;
			$logData['api_url'] = $payLoad['api_url'];
			$logData['api_request_type'] = JOURNEY_API_TYPE_REQUEST;
			$logData['api_data'] = $payLoad;
			$logData['api_status_message'] = JOURNEY_API_STATUS_ERROR_MESSAGE;
			$logData['api_status_code'] = JOURNEY_API_STATUS_ERROR_CODE;
			if ($headers['X-Api-Type'] == "PAYMENT_INIT") {
				$logData = $this->preparePaymentLogData($logData, $payLoad);
				// Save payment log
				$this->paymentLogRepo->save($logData['logData']);
			} else {
				$apiLogRepo->save($logData);
			}
			return $responseData;
		}
	}

	/**
	 * Guzzle Vendor api call method
	 *
	 * @param  $payload, $headers
	 * @return mixed
	 */
	public function guzzleApiCall($payLoad, $headers = [])
	{
		$apiLogRepo = new ApiLogRepository();
		$responseBody = '';
		(app()->environment()) == 'sit' ?  $verifySsl = false : $verifySsl = true;
		try {
			// prepare log data.
			$logData['api_source'] = $headers['X-Api-Source'];
			$logData['api_source_page'] = $headers['X-Api-Source-Page'];
			$logData['api_type'] = $headers['X-Api-Type'];
			$logData['api_header'] = $headers;
			$logData['api_url'] = $payLoad['api_url'];
			$logData['api_request_type'] =  JOURNEY_API_TYPE_REQUEST;
			$logData['api_data'] = $payLoad;
			$logData['api_status_message'] = config('journey/http-status.success.message');
			$logData['api_status_code'] = config('journey/http-status.success.code');
			if ($headers['X-Api-Type'] == "PAYMENT_INIT") {
				$logData = $this->preparePaymentLogData($logData, $payLoad);
				$this->paymentLogRepo->save($logData['logData']);
			} else {
				$apiLogRepo->save($logData);
			}
			//Curl call
			$response = $this->apiCallInit($headers, $payLoad, $verifySsl);

			$responseBody = $response->getBody(true);
			// update response column after core call
			if (empty($responseBody) === false) {
				$responseData = [
					'status' => true,
					'response' => json_decode($responseBody->getContents(), true)
				];
				// prepare log data.
				$logData['api_source'] = $headers['X-Api-Source'];
				$logData['api_source_page'] = $headers['X-Api-Source-Page'];
				$logData['api_type'] = $headers['X-Api-Type'];
				$logData['api_header'] = $headers;
				$logData['api_url'] = $payLoad['api_url'];
				$logData['api_request_type'] = config('constants/apiType.RESPONSE');
				$logData['api_data'] = $responseData;
				$logData['api_status_message'] = config('journey/http-status.success.message');
				$logData['api_status_code'] = config('journey/http-status.success.code');
				if ($headers['X-Api-Type'] == "PAYMENT_INIT") {
					$logData = $this->preparePaymentLogData($logData, $payLoad);
					// Save payment log
					$this->paymentLogRepo->save($logData['logData']);
					// Save payment transactionn data
					$this->paymentRepo->upsertPaymentData($logData['paymentData']);
				} else {
					$apiLogRepo->save($logData);
				}
				// check response
				if ($responseData['response'] === null) {
					$formatData = json_decode($responseBody, true);
					if (isset($formatData['ErrorCode']) && $formatData['ErrorCode'] !== '' && $formatData['ErrorCode'] == 200) {

						$responseData = [
							'status' => true,
							'response' => $formatData
						];
					} else {
						$responseData = [
							'status' => false,
							'response' => 'Unable to process data.'
						];
					}
				}
			} else {
				// prepare log data.
				$logData['api_source'] = $headers['X-Api-Source'];
				$logData['api_source_page'] = $headers['X-Api-Source-Page'];
				$logData['api_type'] = $headers['X-Api-Type'];
				$logData['api_header'] = $headers;
				$logData['api_url'] = $payLoad['api_url'];
				$logData['api_request_type'] = config('constants/apiType.RESPONSE');
				$logData['api_data'] = $payLoad;
				$logData['api_status_message'] = config('journey/http-status.failure.message');
				$logData['api_status_code'] = config('journey/http-status.failure.code');
				if ($headers['X-Api-Type'] == "PAYMENT_INIT") {
					$logData = $this->preparePaymentLogData($logData, $payLoad);
					// Save payment log
					$this->paymentLogRepo->save($logData['logData']);
				} else {
					$apiLogRepo->save($logData);
				}
				$responseData = [
					'status' => config('journey/http-status.failure.status'),
					'response' => config('journey/http-status.failure.message')
				];
			}
			return $responseData;
		} catch (HttpClientException | ServerException | ClientException $e) {
			Log::info("Api__Exceptions guzzleApiCall " . $headers['X-Api-Type'] . " " . $e->getMessage() . "\n");
			$responseData['code'] = JOURNEY_API_STATUS_ERROR_CODE;
			$responseData['status'] = JOURNEY_API_STATUS_ERROR;
			$responseData['message'] = JOURNEY_API_STATUS_ERROR_MESSAGE;
			// prepare log data.
			$logData['api_source'] = $headers['X-Api-Source'];
			$logData['api_source_page'] = $headers['X-Api-Source-Page'];
			$logData['api_type'] = $headers['X-Api-Type'];
			$logData['api_header'] = $headers;
			$logData['api_url'] = $payLoad['api_url'];
			$logData['api_request_type'] = JOURNEY_API_TYPE_REQUEST;
			$logData['api_data'] = $payLoad;
			$logData['api_status_message'] = JOURNEY_API_STATUS_ERROR_MESSAGE;
			$logData['api_status_code'] = JOURNEY_API_STATUS_ERROR_CODE;
			if ($headers['X-Api-Type'] == "PAYMENT_INIT") {
				$logData = $this->preparePaymentLogData($logData, $payLoad);
				// Save payment log
				$this->paymentLogRepo->save($logData['logData']);
			} else {
				$apiLogRepo->save($logData);
			}
			return $responseData;
		} catch (ConnectException $e) {
			Log::info("connection__Exceptions " . $payLoad['api_url'] . " " . $e->getMessage() . "\n");
			$responseData['code'] = config('journey/http-status.timeout.code');
			$responseData['status'] = config('journey/http-status.timeout.status');
			$responseData['message'] =  trans('http-status.timeout.message');
			// prepare log data.
			$logData['api_source'] = $headers['X-Api-Source'];
			$logData['api_source_page'] = $headers['X-Api-Source-Page'];
			$logData['api_type'] = $headers['X-Api-Type'];
			$logData['api_header'] = $headers;
			$logData['api_url'] = $payLoad['api_url'];
			$logData['api_request_type'] = JOURNEY_API_TYPE_REQUEST;
			$logData['api_data'] = $payLoad;
			$logData['api_status_message'] = JOURNEY_API_STATUS_ERROR_MESSAGE;
			$logData['api_status_code'] = JOURNEY_API_STATUS_ERROR_CODE;
			if ($headers['X-Api-Type'] == "PAYMENT_INIT") {
				$logData = $this->preparePaymentLogData($logData, $payLoad);
				// Save payment log
				$this->paymentLogRepo->save($logData['logData']);
			} else {
				$apiLogRepo->save($logData);
			}
			return $responseData;
		}
	}

	/**
	 * Api call Init
	 *
	 * @param $headers, $payload, $verifySsl
	 * @return mixed
	 */
	public function apiCallInit($headers, $payLoad, $verifySsl)
	{
		$client = app()->make(Client::class);
		$url = env('PAYTM_URL');
		if ($payLoad['method'] == 'POST') {
			if ($headers['X-Api-Type'] == "PAYMENT_INIT") {
				$response = $client->request('POST', $url, [
					'form_params' => [
						'data' => json_encode($payLoad['data']),
						'token' => $payLoad["token"]
					]
				]);
			} else {
				$response = $client->post($payLoad['api_url'], [
					'json' => $payLoad['data'],
					'headers' => $headers,
					'timeout' => config('journey/api.timeout'),
					'verify' => $verifySsl,
				]);
			}
		} elseif ($payLoad['method'] == 'GET') {
			$response = $client->get($payLoad['api_url'], [
				'headers' => $headers,
				'timeout' => config('journey/api.timeout'),
				'verify' => $verifySsl,
			]);
		}
		return $response;
	}

	/* get authentication bearer token */
	public function getCoreAuthToken($apiLogRepo, $apiLogData = '')
	{
		try {
			$apiRequest = array(
				'PartnerID' => env('EXIST_PARTNERID'),
				'UserID' => env('EXIST_USERID'),
				'Password' => env('EXIST_PASSWORD')
			);
			$requestData = [
				'headers' => ['Accept' => 'application/json'],
				'timeout' => 300,
				'verify' => false
			];
			$apiType = config('constants/apiType.CORE_API_AUTHENTICATE');
			$apiUrl = env('CORE_API_URL') . 'Auth/Authenticate';
			$type =  config('constants/apiSource.CORE');
			$response  = $this->clientApiCall($apiRequest, $apiUrl, $apiType, $apiLogData, "POST", $requestData, $type);
			return $response;
		} catch (Throwable  | HttpClientException $throwable) {
			Log::info($throwable->getMessage());
			$date   = CURRENT_DATE;
			$logUpdate = ([
				"api_type" =>  $apiType,
				"api_source_page" => "AUTHENTICATE",
				"api_source" => config('constants/apiSource.CORE'),
				"request" => empty($apiRequest) === false ? ($apiRequest) : "",
				"response" => empty($response) === false ? $response : "",
				"url" => $apiUrl,
				"created_at" => $date,
				"updated_at" => $date,
				"api_status" => config('constants/apiStatus.FAILURE')
			]);
			$apiLogRepo->save($logUpdate);
			$errorResponse['msg'] = "Oops, something went wrong";
			return $errorResponse;
		}
	}

	/**
	 * Prepare Payment Log Data
	 *
	 * @param  $logData,$payload
	 * @return mixed
	 */
	public function preparePaymentLogData($logData, $payLoad)
	{
		$pt['invoice_number'] = $logData['invoice_number'] = $payLoad['data']['INVOICE_NUMBER'];
		$pt['lead_id'] = $logData['lead_id'] = $payLoad['lead_id'];
		$pt['quote_id'] = $logData['quote_id'] = $payLoad['quote_id'];
		$logData['mobile_number'] = $payLoad['mobile_number'];
		$logData['master_product_id'] = $payLoad['master_product_id'];
		$logData['digital_transaction_no'] = $payLoad['digital_transaction_no'];
		$pt['digital_transaction_no'] = $payLoad['digital_transaction_no'];
		$pt['amount'] = $payLoad['data']['TOTAL_AMOUNT'];
		$pt['mode'] = $payLoad['data']['MODE'];
		$pt['customer_var'] = $payLoad['data']['CUSTOM_DATA']['description'];
		$pt['status'] = config('constants/paymentStatus.INIT');
		$pt['request'] = json_encode($payLoad);
		$pt['payment_gateway_id'] = "1";
		$retunData['logData'] = $logData;
		$retunData['paymentData'] = $pt;
		return $retunData;
	}

	/**
	 * mobile number Spam check
	 *
	 * @param  $mobile
	 * @return bool
	 */
	public function isSpam($phone): bool
	{
		return ((!$this->phonenumber($phone)) || ($this->checkInValidPhone($phone))) ? true : false;
	}

	/**
	 * mobile number invalid check
	 *
	 * @param  $mobile
	 * @return bool
	 */
	public function checkInValidPhone($mobile): bool
	{
		foreach (count_chars($mobile, 1) as  $val) {
			if ($val >= 10) {
				return true;
			}
		}
		return false;
	}
	/**
	 * mobile number regex check
	 *
	 * @param  $mobile
	 * @return bool
	 */
	public function phonenumber($phone): bool
	{
		$regex = '/^[6-9][0-9]{9}+$/';
		return (preg_match($regex, $phone)) ? true : false;
	}
	/**
	 * Language map for whatsapp consent
	 *
	 * @param  $languageArray
	 * @return string
	 */
	public function langMappingForConsent($languageArray): string
	{
		foreach ($languageArray as $language) {
			if ($language['Description'] == "English") {
				return $language['Code'];
			}
		}
	}
	public function createPPCId()
	{
		$CurrentTime = strtotime("now");
		$randomString = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 1, 4);
		$PPCID =  "PPC" . $randomString . $CurrentTime;
		return $PPCID;
	}
	public function covertMobileNO($mobile)
	{
		if (empty($mobile) === false) {
			$originalNumber = $mobile;
			$replacement = 'xxxxxx';
			$newNumber = substr_replace($originalNumber, $replacement, 2, 6);
			return $newNumber;
		} else {
			return null;
		}
	}
	public function covertMobileEmail($email)
	{
		if (empty($email) === false) {
			$firstTwoChars = substr($email, 0, 3);
			$lastTwoChars = substr($email, -2);
			$newEmail = $firstTwoChars . str_repeat('x', strlen($email) - 4) . $lastTwoChars;
			return $newEmail;
		} else {
			return null;
		}
	}

	public function generateRandomString($type)
	{
		if ($type == "paymentTransactionId") {
			return strtotime(CURRENT_DATE) . random_int(10000, 99999);
		} elseif ($type == "orderId") {
			$randomNumber = random_int(100000, 999999);
			$length = 4;
			$randomString = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 1, $length);
			return  $randomString . $randomNumber . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 1, 1);
		} elseif ($type == "digitalTransactionID") {
			$randomString = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 1, 3);
			return "SHFL" . $randomString . strtotime(CURRENT_DATE);
		}
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

	public function getLeadFromMobile($mobileNumber)
	{
		return HjLead::where('mobile_number', $mobileNumber)->where('is_applicant', 1)->first();
	}

	public function validationCheck($request, $rules)
	{
		$validator = $this->validator($request->all(), $rules);
		if ($validator !== false) {
			return $validator;
		}
		return true;
	}

	public function otpEmptyCheck($otp)
	{
		return empty($otp) === false ? $otp : "";
	}

	public function contactNumberEmptyCheck($mobileNnumber)
	{
		return  empty($mobileNnumber) === false ? sprintf("91%s", $mobileNnumber) : "";
	}

	public function customerFetchApi($requestData)
	{
		return $this->customerFetchApiForPanHistory($requestData, config('constants/apiSourcePage.HOME_PAGE'));
	}
}
