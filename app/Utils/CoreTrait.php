<?php

namespace App\Utils;

use App\Repositories\HousingJourney\EmploymentDetailRepository;
use App\Repositories\HousingJourney\PersonalDetailRepository;
use App\Repositories\HousingJourney\AddressRepository;
use App\Repositories\HousingJourney\PropertyLoanDetailRepository;
use Throwable;
use Exception;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Log;
use App\Repositories\HousingJourney\LeadRepository;
use App\Repositories\HousingJourney\ApplicationRepository;
use App\Repositories\HousingJourney\DocumentRepository;
use App\Repositories\HousingJourney\PaymentTransactionRepository;
use App\Repositories\HousingJourney\ImpressionRepository;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use App\Repositories\ApiLogRepository;
use Carbon\Carbon;
use App\Repositories\HousingJourney\BreLogRepository;
use App\Repositories\HousingJourney\EligibilityRepository;
use App\Repositories\HousingJourney\FinalSubmitLogRepository;
use App\Mail\ExportData;
use Illuminate\Support\Facades\Mail;
use App\Repositories\HousingJourney\SmsLogRepository;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

trait CoreTrait
{
	/**
	 * define the Business Rule Engine level one and level two  method
	 * 
	 * @return mixed
	 */
	public function __construct(EmploymentDetailRepository $employmentDetailsRepo, PersonalDetailRepository $personalRepo, AddressRepository $addressRepo, PropertyLoanDetailRepository $propertyRepo, LeadRepository $leadRepo, ApplicationRepository $applicationRepo, DocumentRepository $documentRepo, PaymentTransactionRepository $paymentTransRepo, ImpressionRepository $impressionRepo, ApiLogRepository $apiLogRepo)
	{
		$this->employmentDetailsRepo = $employmentDetailsRepo;
		$this->personalRepo = $personalRepo;
		$this->addressRepo = $addressRepo;
		$this->propertyRepo = $propertyRepo;
		$this->leadRepo = $leadRepo;
		$this->applicationRepo = $applicationRepo;
		$this->documentRepo = $documentRepo;
		$this->paymentTransRepo = $paymentTransRepo;
		$this->impressionRepo = $impressionRepo;
		$this->apiLogRepo = $apiLogRepo;
	}
	/**
	 * prepare BRE data
	 *
	 * @param  $request, $payLoad
	 */
	public function prepareBREData($request)
	{
		try {
			$employmentDetailsRepo = new EmploymentDetailRepository();
			$personalDetailRepo = new PersonalDetailRepository();
			$applicationRepo = new ApplicationRepository();
			$propertyRepo = new PropertyLoanDetailRepository();
			$eligibilityRepo = new EligibilityRepository();
			$leadData = $this->leadRepo->view($request->lead_id);
			$employmentData = $employmentDetailsRepo->view($request->all());
			$personalData = $personalDetailRepo->view($request->all());
			$applicationData = $applicationRepo->getApplication($request->quote_id);
			$propertyData = $propertyRepo->view($request->all());
			$eligibilityData = $eligibilityRepo->getBre1Eligibile($request->all());
			$finalBREData = [];
			$employmentType = '';
			switch ($employmentData->employmentType['handle']) {
				case 'salaried':
					$employmentType = 'S';
					break;
				case 'self-employed-professional':
					$employmentType = 'SEP';
					break;
				case 'self-employed-non-professional':
					$employmentType = 'SENP';
					break;
				default:
					$employmentType = '';
					break;
			}
			$dob = new Carbon($personalData['dob']);
			$today   = new Carbon('today');
			$age = $dob->diff($today)->y;
			$propertyType = '';
			if ($propertyData && $propertyData->propertyType) {
				switch ($propertyData->propertyType->handle) {
					case 'residential':
						$propertyType = 'RP';
						break;
					case 'commercial':
						$propertyType = 'CP';
						break;
					case 'industrial':
						$propertyType = 'IP';
						break;
					default:
						$propertyType = '';
						break;
				}
			}
			$propertyCurrentState = "";
			if ($propertyData && $propertyData->propertyCurrentState) {
				switch ($propertyData->propertyCurrentState->handle) {
					case 'self-occupied':
						$propertyCurrentState = 'SOC';
						break;
					case 'vacant':
						$propertyCurrentState = 'VAC';
						break;
					case 'underconstruction':
						$propertyCurrentState = 'U';
						break;
					case 'seller-occupied':
						$propertyCurrentState = 'O';
						break;
					case 'rental':
						$propertyCurrentState = 'REN';
						break;
					default:
						$propertyCurrentState = "";
						break;
				}
			}
			$breData['LedPK'] = $leadData->id;
			$breData['LapFK'] = 1;
			$breData['Prd'] = $applicationData->masterproduct[0]['code'] ?? "";
			$breData["Obl"] = $employmentData['monthly_emi'] ?? 0;
			$breData["ACusCat"] = $employmentType;
			$breData["MinAge"] = $age;
			$breData["CurWrkExp"] = ($employmentData['current_experience'] * 12) ?? 0;
			$breData["TotWrkExp"] = ($employmentData['total_experience'] * 12) ?? 0;
			$breData["NetMonthlySal"] = $employmentData['net_monthly_salary'] ?? 0;
			$breData["Oth_Income"] = $employmentData['other_income'] ?? 0;
			$breData["GrossProfit"]   = $employmentData['net_monthly_profit'] ?? 0;
			$breData["GrossReceipt"] = $employmentData['gross_receipt'] ?? 0;
			$breData["ProfessionTyp"] = $employmentData->professionalType->name ?? "";
			$breData["TurnOver"] = $employmentData['net_monthly_sales'] ?? 0;
			$breData["Indus_Type"] = $employmentData->industryType->name ?? "";
			$breData["Indus_Segmt"] = $employmentData->industrySegment->name ?? "";
			if ($request['bre_type'] == config('constants/apiType.BRE_LEVEL_ONE')) {
				$breData["outstdPrinciAmt"] = 0;
				$breData['Remaining_Tenre'] = 0;
				$breData['outstdEmi'] = 0;
				$breData['Prpty'] = "";
				$breData['PrpOcc'] = "";
				$breData['MarketValue'] = 0;
			} else {
				$breData["outstdPrinciAmt"] = $propertyData['outstanding_loan_amount'] ?? 0;
				$breData['Remaining_Tenre'] = $propertyData['outstanding_loan_tenure'] ?? 0;
				$breData['outstdEmi'] = $propertyData['monthly_installment_amount'] ?? 0;
				$breData['Prpty'] = $propertyCurrentState ?? "";
				$breData['PrpOcc'] = $propertyType ?? "";
				$breData['MarketValue'] = $propertyData['cost'] ?? 0;
			}
			$breData['Businessvintage'] = ($employmentData['business_vintage'] * 12) ?? 0;
			if ($employmentType == 'SEP' || $employmentType == 'SENP') {
				$breData["TotWrkExp"] = ($employmentData['business_vintage'] * 12) ?? 0;
			}
			$breData['BREValue']  = $request['bre_type'] == config('constants/apiType.BRE_LEVEL_TWO') ? 2 : 1;
			array_push($finalBREData, $breData);
			if (
				$eligibilityData && $eligibilityData->is_co_applicant == 1
				&& $request['stage'] != config('constants/apiSourcePage.EMPLOYMENT_DETAIL_PAGE')
			) {
				$coApplicantData = $this->leadRepo->getCoApplicantData($request->all());
				if ($coApplicantData) {
					$reqData['lead_id'] = $coApplicantData->co_applicant_id;
					$reqData['quote_id'] = $request['quote_id'];
					$coApplicantemploymentData = $employmentDetailsRepo->view($reqData);
					$coApplicantpersonalData = $personalDetailRepo->view($reqData);
					$coAppEmploymentType = '';
					if ($coApplicantemploymentData) {
						switch ($coApplicantemploymentData->employmentType['handle']) {
							case 'salaried':
								$coAppEmploymentType = 'S';
								break;
							case 'self-employed-professional':
								$coAppEmploymentType = 'SEP';
								break;
							case 'self-employed-non-professional':
								$coAppEmploymentType = 'SENP';
								break;
							default:
								$coAppEmploymentType = '';
								break;
						}
					}
					$dob = new Carbon($coApplicantpersonalData['dob']);
					$today   = new Carbon('today');
					$age = $dob->diff($today)->y;
					if (
						$coApplicantpersonalData && $coApplicantemploymentData
						&& $coApplicantemploymentData->is_income_proof_document_available == 1
					) {
						$coApplicantBreData['LedPK'] = $leadData->id;
						$coApplicantBreData['LapFK'] = 2;
						$coApplicantBreData['Prd'] = $applicationData->masterproduct[0]['code'] ?? "";
						$coApplicantBreData["Obl"] = $coApplicantemploymentData['monthly_emi'] ?? 0;
						$coApplicantBreData["ACusCat"] = $coAppEmploymentType;
						$coApplicantBreData["MinAge"] = $age;
						$coApplicantBreData["CurWrkExp"] = ($coApplicantemploymentData['current_experience'] * 12) ?? 0;
						$coApplicantBreData["TotWrkExp"] = ($coApplicantemploymentData['total_experience'] * 12) ?? 0;
						$coApplicantBreData["NetMonthlySal"] = $coApplicantemploymentData['net_monthly_salary'] ?? 0;
						$coApplicantBreData["Oth_Income"] = $coApplicantemploymentData['other_income'] ?? 0;
						$coApplicantBreData["GrossProfit"]   = $coApplicantemploymentData['net_monthly_profit'] ?? 0;
						$coApplicantBreData["GrossReceipt"] = $coApplicantemploymentData['gross_receipt'] ?? 0;
						$coApplicantBreData["ProfessionTyp"] = $coApplicantemploymentData->professionalType->name ?? "";
						$coApplicantBreData["TurnOver"] = $coApplicantemploymentData['net_monthly_sales'] ?? 0;
						$coApplicantBreData["Indus_Type"] = $coApplicantemploymentData->industryType->name ?? "";
						$coApplicantBreData["Indus_Segmt"] = $coApplicantemploymentData->industrySegment->name ?? "";
						if ($request['bre_type'] == config('constants/apiType.BRE_LEVEL_ONE')) {
							$coApplicantBreData["outstdPrinciAmt"] = 0;
							$coApplicantBreData['Remaining_Tenre'] = 0;
							$coApplicantBreData['outstdEmi'] =  0;
							$coApplicantBreData['Prpty'] = "";
							$coApplicantBreData['PrpOcc'] = "";
							$coApplicantBreData['MarketValue'] = 0;
						} else {
							$coApplicantBreData["outstdPrinciAmt"] = $propertyData['outstanding_loan_amount'] ?? 0;
							$coApplicantBreData['Remaining_Tenre'] = $propertyData['outstanding_loan_tenure'] ?? 0;
							$coApplicantBreData['outstdEmi'] =  $propertyData['monthly_installment_amount'] ?? 0;
							$coApplicantBreData['Prpty'] = $propertyCurrentState ?? "";
							$coApplicantBreData['PrpOcc'] = $propertyType ?? "";
							$coApplicantBreData['MarketValue'] = $propertyData['cost'] ?? 0;
						}
						$coApplicantBreData['Businessvintage'] = ($coApplicantemploymentData['business_vintage'] * 12) ?? 0;
						$coApplicantBreData['BREValue']  = $request['bre_type'] == config('constants/apiType.BRE_LEVEL_TWO') ? 2 : 1;
						if ($coAppEmploymentType == 'SEP' || $coAppEmploymentType == 'SENP') {
							$coApplicantBreData["TotWrkExp"] = ($coApplicantemploymentData['business_vintage'] * 12) ?? 0;
						}
						array_push($finalBREData, $coApplicantBreData);
					}
				}
			}
			$aesEncryption = $this->aesEncryption($finalBREData);
			$payLoad['data'] = $aesEncryption;
			$payLoad['api_url'] = env('CORE_API_URL') . 'ProcessBRE';
			$payLoad['api_type'] =  $request['bre_type'];
			$payLoad['method'] = "POST";
			$payLoad['type'] = config('constants/apiSource.CORE');
			$logData['lead_id'] = $request->lead_id;
			$logData['quote_id'] = $request->quote_id;
			$logData['mobile_number'] = $leadData->mobile_number;
			$logData['master_product_id'] = $request->master_product_id;
			$logData['api_source'] = $payLoad['type'];
			$logData['api_source_page'] = $request['stage'];
			$logData['api_type'] = $payLoad['api_type'];
			$logData['api_header'] = $request->header();
			$logData['api_url'] = $payLoad['api_url'];
			$logData['api_request_type'] = config('constants/apiType.REQUEST');
			$logData['api_data'] = json_encode($finalBREData);
			$logData['api_status_code'] = config('journey/http-status.success.code');
			$logData['api_status_message'] = config('journey/http-status.success.message');
			$breLog = new BreLogRepository();
			$breLog->save($logData);
			$authToken = $this->coreAuthTokenApiCall();
			$updatedToken = str_replace('"', "", $authToken);
			$cleanedToken = str_replace('\\', '', $updatedToken);
			$apiResponse = $this->coreClientApiCall($payLoad, [
				'Content-Type' => 'application/json',
				'X-Api-Source' => $payLoad['type'],
				'X-Api-Type' => $payLoad['api_type'],
				'X-Api-Source-Page' => $request['stage'],
				'X-Api-Url' => $payLoad['api_url'],
				'AuthTime' => $cleanedToken
			]);
			// insert into bre logdata
			$logData['lead_id'] = $request->lead_id;
			$logData['quote_id'] = $request->quote_id;
			$logData['mobile_number'] = $leadData->mobile_number;
			$logData['master_product_id'] = $request->master_product_id;
			$logData['api_source'] = $payLoad['type'];
			$logData['api_source_page'] = $request['stage'];
			$logData['api_type'] = $payLoad['api_type'];
			$logData['api_header'] = $request->header();
			$logData['api_url'] = $payLoad['api_url'];
			$logData['api_request_type'] = config('constants/apiType.RESPONSE');
			$logData['api_data'] = $apiResponse;
			$logData['api_status_code'] = $apiResponse == 'Error:Contact Administator' ? config('journey/http-status.error.code') : config('journey/http-status.success.code');
			$logData['api_status_message'] = $apiResponse == 'Error:Contact Administator' ? config('journey/http-status.error.message') : config('journey/http-status.success.message');
			if ($apiResponse == config('journey/http-status.timeout.message')) {
				$logData['api_status_code'] = config('journey/http-status.timeout.code');
				$logData['api_status_message'] = config('journey/http-status.timeout.message');
			}
			$breLog = new BreLogRepository();
			$breLog->save($logData);
			return $apiResponse;
		} catch (Throwable | HttpClientException | ClientException $throwable) {
			// insert into bre logdata
			$leadRepo = new LeadRepository();
			$leadData = $leadRepo->view($request->lead_id);
			$logData['lead_id'] = $request->id;
			$logData['quote_id'] = $request->quote_id;
			$logData['mobile_number'] = $leadData->mobile_number;
			$logData['master_product_id'] = $request->master_product_id;
			$logData['api_source'] = config('constants/apiSource.CORE');
			$logData['api_source_page'] = $request['stage'];
			$logData['api_type'] = $request['bre_type'];
			$logData['api_header'] = $request->header();
			$logData['api_url'] = env('CORE_API_URL') . 'ProcessBRE';
			$logData['api_request_type'] = config('constants/apiType.REQUEST');
			$logData['api_data'] = $request->all();
			$logData['api_status_code'] = config('journey/http-status.error.code');
			$logData['api_status_message'] = config('journey/http-status.error.message');
			$breLog = new BreLogRepository();
			$breLog->save($logData);
			Log::info("CoreTrait prepareBREData " . $throwable->__toString());
			if (in_array(env('APP_ENV'),  config('constants/smsAndEmailTemplateCode.EXCEPTION_ENVIRONMENT'))) {
				$this->exceptionMailTrigger($throwable->__toString(), $logData['api_url']);
			}
			return config('journey/http-status.error.message');
		}
	}
	public function prepareFinalSubmitData($request)
	{
		try {
			$leadData = null;
			$propertyData = null;
			$paymentData = null;
			$allApplicants = [];
			$employmentData = null;
			$permanentAddressData = null;
			$currentAddressData = null;
			$applicationRepo = new ApplicationRepository();
			$personalRepo = new PersonalDetailRepository();
			$employmentDetailsRepo = new EmploymentDetailRepository();
			$propertyRepo = new PropertyLoanDetailRepository();
			$addressRepo = new AddressRepository();
			$leadRepo = new LeadRepository();
			$documentRepo = new DocumentRepository();
			$paymentTransRepo = new PaymentTransactionRepository();
			$eligiRepo = new EligibilityRepository();
			$breRepo = new BreLogRepository();
			$applicationData = $applicationRepo->getApplication($request['quote_id']);
			if (!$applicationData && empty($applicationData)) {
				return false;
			}
			$request['lead_id'] = $applicationData['lead_id'];
			$leadInfoData = $leadRepo->view($request['lead_id']);
			$offerAmount = $applicationData['offer_amount'];
			$personalData = $personalRepo->view($request);
			$propertyInfo = $propertyRepo->view($request);
			$documentInfo = $documentRepo->viewForFinalSumbit($request);
			$employmentInfo = $employmentDetailsRepo->view($request);
			$coApplicantData = $leadRepo->getCoApplicantData($request);
			$eligibilityData = $eligiRepo->getBre1Eligibile($request);
			$permanentAddressInfo = $addressRepo->getAddressDetail($request, 'permanent_address');
			$currentAddressInfo = $addressRepo->getAddressDetail($request, 'current_address');
			$paymentData['payment_transaction_id'] = $applicationData['payment_transaction_id'] ?? "";
			$paymentData['quote_id'] = $request['quote_id'];
			$paymentTransactionInfo = $paymentTransRepo->getTransactionData($paymentData);
			//document info
			if ($documentInfo) {
				$documentData = [];
				foreach ($documentInfo as $documents) {
					$document['document_type_id'] = $documents->document->master_id ?? 0;
					$document['document_type'] = $documents->document->name ?? "";
					$document['file_url'] = $documents->document_saved_location ?? "";
					$documentData[] = $document;
				}
			}
			// lead info
			if ($applicationData && $leadInfoData) {
				$leadData['lead_id'] = $applicationData['lead_id'] ?? 0;
				$leadData['quote_id'] = $applicationData['quote_id'] ?? "";
				$leadData['product_id'] = $applicationData['masterproduct'][0] ? $applicationData['masterproduct'][0]->product_id : 0;
				$leadData['loan_requested_amount'] = $applicationData['loan_amount'] ?? 0;
				$leadData['mobile_number'] = $leadInfoData['mobile_number'] ?? "";
				$leadData['pincode'] = $leadInfoData['pincodeData']['code'] ?? "";
				$leadData['is_being_assisted'] = $leadInfoData['is_being_assisted'] ?? "";
				$leadData['partner_code'] = $leadInfoData['partner_code'] ?? "";
				$leadData['partner_name'] = $leadInfoData['partner_name'] ?? "";
				$leadData['sub_partner_code'] = $leadInfoData['sub_partner_code'] ?? "";
				$leadData['pan_number'] = $personalData['pan'] ?? "";
				$leadData['name'] = $leadInfoData['name'] ?? "";
				if ($personalData && $personalData['dob']) {
					$leadDob = Carbon::createFromFormat('Y-m-d', $personalData['dob'])->format('d-m-Y');
				}
				$leadData['dob'] = $leadDob ?? "";
				$leadData['gender'] = $personalData['gender'] ?? "";
				$leadData['email_id'] = $personalData['email'] ?? "";
				$leadData['applicant_entity_type'] = "Individual";
				$leadData['source'] =   ($applicationData['is_paid']  == "1" || $applicationData['is_paid']  == 1) ? "Website-Express" : "Website-Digital";
				$leadData['SubSource'] = "CCMS";
			}
			// applicant info
			if ($leadInfoData) {
				$applicantData['applicant']['is_primary'] = 1;
				$applicantData['applicant']['emptyp'] = $employmentInfo['employmentType']['name'] ?? "";
				$applicantData['applicant']['name'] = $personalData['full_name'] ?? "";
				$applicantData['applicant']['mobile_number'] = $leadInfoData['mobile_number'] ?? 0;
				$applicantData['applicant']['pan_number'] = $personalData['pan'] ?? "";
				$applicantData['applicant']['home_extension'] = $leadInfoData['home_extension'] ?? "";
				$applicantData['applicant']['cibil_score'] = $applicationData->cibil_score ?? 0;
				$applicantData['applicant']['pincode'] = $leadInfoData['pincodeData']['code'] ?? 0;
				$leadDob = $personalData ? Carbon::createFromFormat('Y-m-d', $personalData['dob'])->format('d-m-Y') : "";
				$applicantData['applicant']['dob'] = $leadDob;
				$applicantData['applicant']['gender'] = $personalData['gender'] ?? "";
				$applicantData['applicant']['email_id'] = $personalData['email'] ?? "";
				// 	//permant address info
				//if ($permanentAddressInfo) {
				$permanentAddressData['plot_number'] = $permanentAddressInfo['address1'] ?? "";
				$permanentAddressData['street'] = $permanentAddressInfo['address2'] ?? "";
				$permanentAddressData['city'] = $permanentAddressInfo['city'] ?? "";
				$permanentAddressData['area'] = $permanentAddressInfo['area'] ?? "";
				$permanentAddressData['pincode'] = $permanentAddressInfo && $permanentAddressInfo['pincodeDetail'] && $permanentAddressInfo['pincodeDetail']['code'] ? $permanentAddressInfo['pincodeDetail']['code'] : 0;
				$permanentAddressData['state'] =  $permanentAddressInfo['state'] ?? "";
				//}
				// 	//current address info
				//if ($currentAddressInfo) {
				$currentAddressData['plot_number'] = $currentAddressInfo['address1'] ?? "";
				$currentAddressData['street'] = $currentAddressInfo['address2'] ?? "";
				$currentAddressData['city'] = $currentAddressInfo['city'] ?? "";
				$currentAddressData['area'] = $currentAddressInfo['area'] ?? "";
				$currentAddressData['pincode'] = $currentAddressInfo &&  $currentAddressInfo['pincodeDetail'] && $currentAddressInfo['pincodeDetail']['code'] ? $currentAddressInfo['pincodeDetail']['code'] : 0;
				$currentAddressData['state'] = $currentAddressInfo['state'] ?? "";
				//}
				// 	// employment info
				//if ($employmentInfo) {
				$employmentData['employment_type'] = $employmentInfo['employmentType']['name'] ?? "";
				$employmentData['business_vintage'] = $employmentInfo['business_vintage'] ?? 0;
				$employmentData['company_name'] = $employmentInfo['company_name'] ?? "";
				$employmentData['net_monthly_salary'] = $employmentInfo['net_monthly_salary'] ?? 0;
				$employmentData['constitution_type'] = $employmentInfo['constitutionTypeDetail']['name'] ?? "";
				$employmentData['current_work_experience'] = $employmentInfo['current_experience'] ?? 0;
				$employmentData['total_work_experience'] = $employmentInfo['total_experience'] ?? 0;
				$employmentData['salary_mode'] = $employmentInfo['employmentSalaryModeDetail']['name'] ?? "";
				$employmentData['gross_receipt'] = $employmentInfo['gross_receipt'] ?? 0;
				$employmentData['profession_type'] = $employmentInfo['professionalType']['name'] ?? "";
				$employmentData['industry_type'] = $employmentInfo['industryType']['name'] ?? "";
				$employmentData['industry_segment'] = $employmentInfo['industrySegment']['name'] ?? "";

				$employmentData['employment_type_id'] = $employmentInfo['employment_type_id'] ?? 0;
				$employmentData['business_vintage_id'] = $employmentInfo['business_vintage'] ?? 0;
				$employmentData['net_monthly_sales'] = $employmentInfo['net_monthly_sales'] ?? 0;
				$employmentData['net_monthly_profit'] = $employmentInfo['net_monthly_profit'] ?? 0;
				$employmentData['constitution_type_id'] = $employmentInfo['constitution_type_id'] ?? 0;
				$employmentData['monthly_emi'] = $employmentInfo['monthly_emi'] ?? 0;
				$employmentData['gross_profit'] = $employmentInfo['gross_profit'] ?? 0;
				$employmentData['other_income'] = $employmentInfo['other_income'] ?? 0;
				$employmentData['is_income_proof_document_available'] = $employmentInfo['is_income_proof_document_available'] ?? 0;
				$employmentData['permanent_address'] = $permanentAddressData ?? [];
				$employmentData['current_address'] = $currentAddressData ?? [];
				//}
				$applicantData['applicant']['employment_details'] = $employmentData;
				$applicantData['applicant']['documents'] = $documentData;
				array_push($allApplicants, $applicantData);
				//if ($eligibilityData && $eligibilityData->is_co_applicant == 1 && $coApplicantData) {
				$reqData['lead_id'] = $coApplicantData['co_applicant_id'] ?? 0;
				$reqData['quote_id'] = $coApplicantData['quote_id'] ?? "";
				$coApplicantLeadInfoData = $leadRepo->view($reqData['lead_id']);
				$coApplicantPersonalData = $personalRepo->view($reqData);
				$coApplicantEmploymentInfo = $employmentDetailsRepo->view($reqData);
				$coApplicantDocumentData = [];

				// if ($coApplicantLeadInfoData && $coApplicantPersonalData && $coApplicantEmploymentInfo) {

				$coApplicantDataInfo['applicant']['is_primary'] = 0;
				$coApplicantDataInfo['applicant']['emptyp'] = $coApplicantEmploymentInfo['employmentType']['name'] ?? "";
				$coApplicantDataInfo['applicant']['name'] = $coApplicantPersonalData['full_name'] ?? "";
				$coApplicantDataInfo['applicant']['mobile_number'] = $coApplicantLeadInfoData['mobile_number'] ?? 0;
				$coApplicantDataInfo['applicant']['pan_number'] = $coApplicantPersonalData['pan'] ?? "";
				$coApplicantDataInfo['applicant']['cibil_score'] = $applicationData->cibil_score ?? 0;
				$coApplicantDataInfo['applicant']['pincode'] = $coApplicantLeadInfoData['pincodeData']['code'] ?? 0;
				$coApplicantLeadDob = $coApplicantPersonalData ? Carbon::createFromFormat('Y-m-d', $coApplicantPersonalData['dob'])->format('d-m-Y') : "";
				$coApplicantDataInfo['applicant']['dob'] = $coApplicantLeadDob;
				$coApplicantDataInfo['applicant']['gender'] = $coApplicantPersonalData['gender'] ?? "";
				$relationshipData = $applicationRepo->getLeadRelationship($reqData);
				$coApplicantDataInfo['applicant']['relationship'] = $relationshipData['relationship']['name'] ?? "";
				// coapplicant employment info
				$coApemploymentData['employment_type_id'] = $coApplicantEmploymentInfo['employmentType']['id'] ?? 0;
				$coApemploymentData['employment_type'] = $coApplicantEmploymentInfo['employmentType']['name'] ?? "";
				$coApemploymentData['business_vintage'] = $coApplicantEmploymentInfo['business_vintage'] ?? 0;
				$coApemploymentData['net_monthly_sales'] = $coApplicantEmploymentInfo['net_monthly_sales'] ?? 0;
				$coApemploymentData['net_monthly_profit'] = $coApplicantEmploymentInfo['net_monthly_profit'] ?? 0;
				$coApemploymentData['constitution_type'] = $coApplicantEmploymentInfo['constitutionTypeDetail']['name'] ?? "";
				$coApemploymentData['current_work_experience'] = $coApplicantEmploymentInfo['current_experience'] ?? 0;
				$coApemploymentData['total_work_experience'] = $coApplicantEmploymentInfo['total_experience'] ?? 0;
				$coApemploymentData['salary_mode'] = $coApplicantEmploymentInfo['employmentSalaryModeDetail']['name'] ?? "";
				$coApemploymentData['company_name'] = $coApplicantEmploymentInfo['company_name'] ?? "";
				$coApemploymentData['gross_profit'] = 0;
				$coApemploymentData['profession_type'] = $coApplicantEmploymentInfo['professionalType']['name'] ?? "";
				$coApemploymentData['industry_type'] = $coApplicantEmploymentInfo['industryType']['name'] ?? "";
				$coApemploymentData['industry_segment'] = $coApplicantEmploymentInfo['industrySegment']['name'] ?? "";

				$coApemploymentData['company_id'] = $coApplicantEmploymentInfo['company_id'] ?? 0;
				$coApemploymentData['total_experience_id'] = $coApplicantEmploymentInfo['total_experience'] ?? 0;
				$coApemploymentData['current_experience_id'] = $coApplicantEmploymentInfo['current_experience'] ?? 0;
				$coApemploymentData['mode_of_salary_id'] = $coApplicantEmploymentInfo['salary_mode_id'] ?? 0;
				$coApemploymentData['industry_type_id'] = $coApplicantEmploymentInfo['industry_type_id'] ?? 0;
				$coApemploymentData['industry_segment_id'] = $coApplicantEmploymentInfo['industry_segment_id'] ?? 0;
				$coApemploymentData['gross_receipt'] = $coApplicantEmploymentInfo['gross_receipt'] ?? 0;
				$coApemploymentData['net_monthly_salary'] = $coApplicantEmploymentInfo['net_monthly_salary'] ?? 0;
				$coApemploymentData['monthly_emi'] = $coApplicantEmploymentInfo['monthly_emi'] ?? 0;
				$coApemploymentData['other_income'] = $coApplicantEmploymentInfo['other_income'] ?? 0;
				$coApemploymentData['is_income_proof_document_available'] = $coApplicantEmploymentInfo['is_income_proof_document_available'] ?? 0;
				$coApplicantDataInfo['applicant']['employment_details'] = $coApemploymentData;
				$coApplicantDataInfo['applicant']['documents'] = $coApplicantDocumentData;
				$coApplicantDataInfo['applicant']['employment_details']['permanent_address'] = [];
				$coApplicantDataInfo['applicant']['employment_details']['current_address'] = [];
				array_push($allApplicants, $coApplicantDataInfo);
				//	}
				//}
			}
			// property info
			//if ($propertyInfo && $employmentInfo) {
			$propertyData['is_property_identified'] = $propertyInfo['is_property_identified'] ?? "";
			$propertyData['loan_amount'] =  $leadInfoData['loan_amount'] ?? 0;
			$propertyData['property_purpose'] =
				$propertyInfo && $propertyInfo['propertyPurpose'] ? $propertyInfo['propertyPurpose']['name'] : "";
			$propertyData['property_purchased_from'] = $propertyInfo['property_purchase_from'] ?? "";
			$propertyData['project_name'] = $propertyInfo['project_name'] ?? "";
			$propertyData['is_loan_free'] = $propertyInfo['is_property_loan_free'] ?? 0;
			$propertyData['existing_loan']['existing_home_loan_provider'] = $propertyInfo['existing_loan_provider_name'] ?? "";
			$propertyData['existing_loan']['original_loan_amount'] = $propertyInfo['original_loan_amount'] ?? 0;
			$propertyData['existing_loan']['original_loan_tenure'] = $propertyInfo['original_loan_tenure'] ?? 0;
			$propertyData['existing_loan']['outstanding_loan_tenure'] = $propertyInfo['outstanding_loan_tenure'] ?? 0;
			$propertyData['existing_loan']['outstanding_loan_amount'] = $propertyInfo['outstanding_loan_amount'] ?? 0;
			$propertyData['property_type'] = $propertyInfo && $propertyInfo['propertyType'] ? $propertyInfo['propertyType']['name'] : "";
			$propertyData['property_current_state'] =
				$propertyInfo && $propertyInfo['propertyCurrentState'] ? $propertyInfo['propertyCurrentState']['name'] : "";
			$propertyData['property_age'] = $propertyInfo['age'] ?? 0;
			$propertyData['property_cost'] = $propertyInfo['cost'] ?? 0;
			$propertyData['cost_of_construction'] = $propertyInfo['construction_cost'] ?? 0;
			$propertyData['monthly_emi'] = $employmentInfo['monthly_emi'] ?? 0;
			$propertyData['pincode'] = $propertyInfo && $propertyInfo['pincodeDetail'] &&  $propertyInfo['pincodeDetail']->code ? $propertyInfo['pincodeDetail']->code : 0;
			$propertyData['city'] = $propertyInfo['city'] ?? "";
			$propertyData['state'] = $propertyInfo['state'] ?? "";
			$propertyData['area'] = $propertyInfo['area'] ?? "";
			//}
			// payment transaction info
			if ($paymentTransactionInfo) {
				$paymentData['transaction_status'] = $paymentTransactionInfo['status'] ?? "";
				$paymentData['transaction_id'] = $paymentTransactionInfo['payment_transaction_id'] ?? "";
				$paymentData['transaction_amount'] = $paymentTransactionInfo['amount'] ?? 0;
				$paymentData['transaction_mode'] = $paymentTransactionInfo['mode'] ?? "";
				$paymentData['transaction_instrument'] = $paymentTransactionInfo['mode'] ?? "";
				$paymentData['transaction_timestamp'] = $paymentTransactionInfo['transaction_time'] ?? "";
				$paymentData['pg_name'] = $paymentTransactionInfo['payment']->name ?? "";
				$paymentData['pg_transaction_id'] = $paymentTransactionInfo['gateway_transaction_id'] ?? "";
			}

			$eligibilityFinalData['deviation'] = $breRepo->getBreDeviation($leadData) ?? "";
			$eligibilityFinalData['bre1_loan_amount'] = (int)$applicationData['bre1_updated_loan_amount'] ?? 0;
			$eligibilityFinalData['bre2_loan_amount'] = (int)$applicationData['bre2_loan_amount'] ?? 0;
			if ($applicationData && $applicationData['bre2_loan_amount'] != null && $applicationData['bre2_loan_amount'] != 0) {
				$offeredAmount = (int) $applicationData['offer_amount'];
			} elseif ($applicationData && $eligibilityFinalData['deviation'] == 'Y') {
				$offeredAmount = 0;
			} else {
				$offeredAmount = (int)$applicationData['loan_amount'];
			}
			$eligibilityFinalData['offer_amount'] = $offeredAmount;
			$eligibilityFinalData['ip_offered_amount'] = $eligibilityFinalData['deviation'] == 'N' && $applicationData['offer_amount'] ? (int)$applicationData['offer_amount'] : 0;
			$eligibilityFinalData['tenure'] = (int) $eligiRepo->getTenure($leadData, $offerAmount) ?? 0;

			$finalData['lead_info'] = $leadData ?? [];
			$finalData['applicants_info'] = $allApplicants ?? [];
			$finalData['property_info'] = $propertyData ?? [];
			$finalData['eligibility_info'] = $eligibilityFinalData ?? [];
			$finalData['payment_transactions_info'] = $paymentData ?? [];
			$submitData['Action'] = 'WebSite';
			$submitData['GlobalDtlsJson'] = json_encode($finalData);
			$finalLogData['Action'] = 'WebSite';
			$finalLogData['GlobalDtlsJson'] = $finalData;
			$logData['lead_id'] = $request['lead_id'];
			$logData['quote_id'] = $request['quote_id'];
			$logData['mobile_number'] = $leadInfoData['mobile_number'];
			$logData['master_product_id'] = $applicationData['master_product_id'];
			$logData['api_source'] = config('constants/apiSource.CORE');
			$logData['api_source_page'] = $request['api_source_page'] ?? null;
			$logData['api_type'] = config('constants/apiType.FINAL_SUBMIT');
			$logData['api_header'] = $request['header'] ?? null;
			$logData['api_url'] = env('CORE_API_URL') . 'WebSiteGpLead';
			$logData['api_request_type'] = config('constants/apiType.REQUEST');
			$logData['api_data'] = $finalLogData;
			$logData['api_status_code'] = config('journey/http-status.success.code');
			$logData['api_status_message'] = config('journey/http-status.success.message');
			$finalLog = new FinalSubmitLogRepository();
			$finalLog->save($logData);
			$requestEncrypt = $this->aesEncryption($submitData);
			$payLoad['data'] = $requestEncrypt;
			$payLoad['api_url'] = env('CORE_API_URL') . 'WebSiteGpLead';
			$payLoad['api_type'] =  config('constants/apiType.FINAL_SUBMIT');
			$payLoad['method'] = "POST";
			$payLoad['type'] = config('constants/apiSource.CORE');
			$authToken = $this->coreAuthTokenApiCall();
			$updatedToken = str_replace('"', "", $authToken);
			$cleanedToken = str_replace('\\', '', $updatedToken);
			$apiResponse = $this->coreClientApiCall($payLoad, [
				'Content-Type' => 'application/json',
				'X-Api-Source' => $payLoad['type'],
				'X-Api-Type' => $payLoad['api_type'],
				'X-Api-Source-Page' => $request['api_source_page'],
				'X-Api-Url' => $payLoad['api_url'],
				'AuthTime' => $cleanedToken
			]);
			$logData['lead_id'] = $request['lead_id'];
			$logData['quote_id'] = $request['quote_id'];
			$logData['mobile_number'] = $leadInfoData['mobile_number'];
			$logData['master_product_id'] = $applicationData['master_product_id'];
			$logData['api_source'] = config('constants/apiSource.CORE');
			$logData['api_source_page'] = $request['api_source_page'] ?? null;
			$logData['api_type'] = config('constants/apiType.FINAL_SUBMIT');
			$logData['api_header'] = $request['header'] ?? null;
			$logData['api_url'] = env('CORE_API_URL') . 'WebSiteGpLead';
			$logData['api_request_type'] = config('constants/apiType.RESPONSE');
			$logData['api_data'] = $apiResponse;
			$logData['api_status_code'] = config('journey/http-status.success.code');
			$logData['api_status_message'] = config('journey/http-status.success.message');
			if ($apiResponse == config('journey/http-status.timeout.message')) {
				$logData['api_status_code'] = config('journey/http-status.timeout.code');
				$logData['api_status_message'] = config('journey/http-status.timeout.message');
			}
			$finalLog = new FinalSubmitLogRepository();
			$finalLog->save($logData);
			if (gettype($apiResponse) == 'string') {
				return  json_decode($apiResponse, true);
			}
			return $apiResponse;
		} catch (Throwable | HttpClientException $throwable) {
			$applicationRepo = new ApplicationRepository();
			$leadRepo = new LeadRepository();
			$getLeadId = $applicationRepo->getQuoteIdDetails($request);
			$leadId = $getLeadId->lead_id ?? null;
			$leadInfoData = $leadRepo->view($leadId);
			$logData['lead_id'] = $leadId;
			$logData['quote_id'] = $request['quote_id'];
			$logData['mobile_number'] = $leadInfoData['mobile_number'];
			$logData['master_product_id'] = $getLeadId['master_product_id'];
			$logData['api_source'] = config('constants/apiSource.CORE');
			$logData['api_source_page'] = $request['api_source_page'] ?? null;
			$logData['api_type'] = config('constants/apiType.FINAL_SUBMIT');
			$logData['api_header'] = $request['header'] ?? null;
			$logData['api_url'] = env('CORE_API_URL') . 'WebSiteGpLead';
			$logData['api_request_type'] = config('constants/apiType.REQUEST');
			$logData['api_data'] = json_encode($request);
			$logData['api_status_code'] = config('journey/http-status.error.code');
			$logData['api_status_message'] = config('journey/http-status.error.message');
			$finalLog = new FinalSubmitLogRepository();
			$finalLog->save($logData);
			if (in_array(env('APP_ENV'),  config('constants/smsAndEmailTemplateCode.EXCEPTION_ENVIRONMENT'))) {
				$this->exceptionMailTrigger($throwable->__toString(), $logData['api_url']);
			}
			return config('journey/http-status.error.message');
			Log::info("CoreTrait prepareFinalSubmitData " . $throwable->__toString());
		}
	}
	public function customerFetchApiForPanHistory($request, $apiSourcePage)
	{
		try {
			$requestEncrypt = $this->aesEncryption($request);
			$payLoad['data'] = $requestEncrypt;
			$payLoad['api_url'] = env('CORE_API_URL') . 'Getcustdedupe';
			$payLoad['api_type'] =  config('constants/apiType.CUST_DEDUPE');
			$payLoad['method'] = "POST";
			$payLoad['type'] = config('constants/apiSource.CORE');
			$authToken = $this->coreAuthTokenApiCall();
			$updatedToken = str_replace('"', "", $authToken);
			$cleanedToken = str_replace('\\', '', $updatedToken);
			$apiResponse = $this->coreClientApiCall($payLoad, [
				'Content-Type' => 'application/json',
				'X-Api-Source' => $payLoad['type'],
				'X-Api-Type' => $payLoad['api_type'],
				'X-Api-Status' => config('constants/apiStatus.INIT'),
				'X-Api-Source-Page' => $apiSourcePage,
				'X-Api-Url' => $payLoad['api_url'],
				'AuthTime' => $cleanedToken
			]);
			if (gettype($apiResponse) == 'string') {
				return  json_decode($apiResponse, true);
			}
			return $apiResponse;
		} catch (Throwable | HttpClientException $throwable) {
			Log::info("CoreTrait customerFetchApiForPanHistory " . $throwable->__toString());
			if (in_array(env('APP_ENV'),  config('constants/smsAndEmailTemplateCode.EXCEPTION_ENVIRONMENT'))) {
				$this->exceptionMailTrigger($throwable->__toString(),  env('CORE_API_URL') . 'Getcustdedupe');
			}
		}
	}
	public function getCibilData($request)
	{
		try {
			$aesEncryption = $this->aesEncryption($request);
			$payLoad['data'] = $aesEncryption;
			$payLoad['api_url'] = env('CORE_API_URL') . 'FetchCibilDetails';
			$payLoad['api_type'] =  config('constants/apiType.FETCH_CIBIL_DATA');
			$payLoad['method'] = "POST";
			$payLoad['api_source'] = config('constants/apiSource.CORE');
			$authToken = $this->coreAuthTokenApiCall();
			$updatedToken = str_replace('"', "", $authToken);
			$cleanedToken = str_replace('\\', '', $updatedToken);
			$apiResponse = $this->coreClientApiCall($payLoad, [
				'Content-Type' => 'application/json',
				'X-Api-Source' => $payLoad['api_source'],
				'X-Api-Type' => $payLoad['api_type'],
				'X-Api-Status' => config('constants/apiStatus.INIT'),
				'X-Api-Source-Page' => config('constants/apiSourcePage.PERSONAL_DETAIL_PAGE'),
				'X-Api-Url' => $payLoad['api_url'],
				'AuthTime' => $cleanedToken
			]);
			if (gettype($apiResponse) == 'string') {
				return json_decode($apiResponse, true);
			}
			return $apiResponse;
		} catch (Throwable | HttpClientException $throwable) {
			Log::info("CoreTrait getCibilData " . $throwable->__toString());
			if (in_array(env('APP_ENV'),  config('constants/smsAndEmailTemplateCode.EXCEPTION_ENVIRONMENT'))) {
				$this->exceptionMailTrigger($throwable->__toString(), env('CORE_API_URL') . 'FetchCibilDetails');
			}
		}
	}
	public function fetchAddressFromKarza($request)
	{
		try {
			$aesEncryption = $this->aesEncryption($request);
			$payLoad['data'] = $aesEncryption;
			$payLoad['api_url'] = env('CORE_API_URL') . 'karzaPan';
			$payLoad['api_type'] =  config('constants/apiType.KARZA_PAN_DATA');
			$payLoad['method'] = "POST";
			$payLoad['api_source'] = config('constants/apiSource.CORE');
			$authToken = $this->coreAuthTokenApiCall();
			$updatedToken = str_replace('"', "", $authToken);
			$cleanedToken = str_replace('\\', '', $updatedToken);
			$apiResponse = $this->coreClientApiCall($payLoad, [
				'Content-Type' => 'application/json',
				'X-Api-Source' => $payLoad['api_source'],
				'X-Api-Type' => $payLoad['api_type'],
				'X-Api-Source-Page' => config('constants/apiSourcePage.ADDRESS_PAGE'),
				'X-Api-Url' => $payLoad['api_url'],
				'AuthTime' => $cleanedToken
			]);
			return $apiResponse;
		} catch (Throwable  | HttpClientException $throwable) {
			Log::info("CoreTrait fetchAddressFromKarza " . $throwable->__toString());
			if (in_array(env('APP_ENV'),  config('constants/smsAndEmailTemplateCode.EXCEPTION_ENVIRONMENT'))) {
				$this->exceptionMailTrigger($throwable->__toString(), config('constants/apiType.KARZA_PAN_DATA'));
			}
		}
	}

	/* partner Fetch API call */
	public function partnerFetchApi($reqData)
	{
		try {
			$aesEncryption = $this->aesEncryption($reqData);
			$payLoad['data'] = $aesEncryption;
			$payLoad['api_url'] = env('CORE_API_URL') . 'GetEmpCdData';
			$payLoad['api_type'] =  config('constants/apiType.PARTNER_FETCH');
			$payLoad['method'] = "POST";
			$payLoad['api_source'] = config('constants/apiSource.CORE');
			$authToken = $this->coreAuthTokenApiCall();
			$updatedToken = str_replace('"', "", $authToken);
			$cleanedToken = str_replace('\\', '', $updatedToken);
			$apiResponse = $this->coreClientApiCall($payLoad, [
				'Content-Type' => 'application/json',
				'X-Api-Source' => $payLoad['api_source'],
				'X-Api-Type' => $payLoad['api_type'],
				'X-Api-Source-Page' => config('constants/apiSourcePage.HOME_PAGE'),
				'X-Api-Url' => $payLoad['api_url'],
				'AuthTime' => $cleanedToken
			]);
			return json_decode($apiResponse, true);
		} catch (Throwable | HttpClientException $throwable) {
			Log::info("CoreTrait partnerFetch " . $throwable->__toString());
			if (in_array(env('APP_ENV'),  config('constants/smsAndEmailTemplateCode.EXCEPTION_ENVIRONMENT'))) {
				$this->exceptionMailTrigger($throwable->__toString(), env('CORE_API_URL') . 'GetEmpCdData');
			}
		}
	}
	public function coreClientApiCall($payLoad, $headers = [])
	{
		try {
			return $this->coreGuzzleApiCall($payLoad, $headers);
		} catch (HttpClientException | ServerException | ClientException $e) {
			Log::info("Api__Exceptions clientApiCall " . $payLoad['api_url'] . " " . $e . "\n");
			$resData = 'Error:Contact Administator';
			// prepare log data.
			$logData['api_source'] = $headers['X-Api-Source'];
			$logData['api_source_page'] = $headers['X-Api-Source-Page'];
			$logData['api_type'] = $headers['X-Api-Type'];
			$logData['api_header'] = $headers;
			$logData['api_url'] = $headers['X-Api-Url'];
			$logData['api_request_type'] = config('constants/apiType.REQUEST');
			$logData['api_data'] = $payLoad;
			$logData['api_status_code'] = config('journey/http-status.error.code');
			$logData['api_status_message'] = config('journey/http-status.error.message');
			$this->apiLogRepo->save($logData);
			if (in_array(env('APP_ENV'),  config('constants/smsAndEmailTemplateCode.EXCEPTION_ENVIRONMENT'))) {
				$this->exceptionMailTrigger($e->getMessage(), $logData['api_url']);
			}
			return $resData;
		} catch (ConnectException $e) {
			Log::info("connection__Exceptions " . $payLoad['api_url'] . " " . $e . "\n");
			$resData = config('journey/http-status.timeout.message');
			// prepare log data.
			$logData['api_source'] = $headers['X-Api-Source'];
			$logData['api_source_page'] = $headers['X-Api-Source-Page'];
			$logData['api_type'] = $headers['X-Api-Type'];
			$logData['api_header'] = $headers;
			$logData['api_url'] = $headers['X-Api-Url'];
			$logData['api_request_type'] = config('constants/apiType.REQUEST');
			$logData['api_data'] = $payLoad;
			$logData['api_status_code'] = config('journey/http-status.timeout.code');
			$logData['api_status_message'] = config('journey/http-status.timeout.message');
			$this->apiLogRepo->save($logData);
			if (in_array(env('APP_ENV'),  config('constants/smsAndEmailTemplateCode.EXCEPTION_ENVIRONMENT'))) {
				$this->exceptionMailTrigger($e->getMessage(), $logData['api_url']);
			}
			return $resData;
		}
	}
	/**
	 * Guzzle Vendor api call method
	 *
	 * @param  $apiUrl, $payload, $apiType, $apiLogData, $method, $headers
	 * @return mixed
	 */
	public function coreGuzzleApiCall($payLoad,  $headers = [])
	{
		try {
			// prepare log data.
			$logData['api_source'] = $headers['X-Api-Source'];
			$logData['api_source_page'] = $headers['X-Api-Source-Page'];
			$logData['api_type'] = $headers['X-Api-Type'];
			$logData['api_header'] = $headers;
			$logData['api_url'] = $payLoad['api_url'];
			$logData['api_request_type'] = config('constants/apiType.REQUEST');
			$logData['api_data'] = $payLoad;
			$logData['api_status_message'] = config('journey/http-status.success.message');
			$logData['api_status_code'] = config('journey/http-status.success.code');
			$apiLogRepo = new ApiLogRepository();
			$apiLogRepo->save($logData);
			$httpClient = new Client();
			$response = $httpClient->post(
				$payLoad['api_url'],
				[
					RequestOptions::BODY => json_encode($payLoad['data']),
					RequestOptions::HEADERS => $headers,
					RequestOptions::TIMEOUT => env('API_TIMEOUT'),
				]
			);
			$result = $response->getBody()->getContents();
			// prepare log data.
			$logData['api_source'] = $headers['X-Api-Source'];
			$logData['api_source_page'] = $headers['X-Api-Source-Page'];
			$logData['api_type'] = $headers['X-Api-Type'];
			$logData['api_header'] = $headers;
			$logData['api_url'] = $payLoad['api_url'];
			$logData['api_request_type'] = config('constants/apiType.RESPONSE');
			$logData['api_data'] = $result;
			$logData['api_status_message'] = config('journey/http-status.success.message');
			$logData['api_status_code'] = config('journey/http-status.success.code');
			$apiLogRepo->save($logData);
			if ($headers['X-Api-Type'] == 'CC_MESSAGE') {
				return $result;
			} else {
				return json_decode($result, true);
			}
		} catch (RequestException $e) {
			// Handle the exception
			$resData = 'Error:Contact Administator';
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
				$logData['api_request_type'] = config('constants/apiType.REQUEST');
				$logData['api_data'] = $payLoad;
				$logData['api_status_code'] = $statusCode;
				$logData['api_status_message'] = $errorBody;
				$apiLogRepo->save($logData);
				Log::error('Request failed with status: ' . $statusCode, ['error' => $errorBody]);
			} else {
				// Handle request-level errors (e.g., network issues)
				Log::error('Request failed: ' . $e->getMessage());
			}
			return $resData;
		} catch (HttpClientException | ServerException | ClientException $e) {
			Log::info("Api__Exceptions coreGuzzleApiCall " . $payLoad['api_url'] . " " . $e->getMessage() . "\n");
			$resData = 'Error:Contact Administator';
			// prepare log data.
			$logData['api_source'] = $headers['X-Api-Source'];
			$logData['api_source_page'] = $headers['X-Api-Source-Page'];
			$logData['api_type'] = $headers['X-Api-Type'];
			$logData['api_header'] = $headers;
			$logData['api_url'] = $payLoad['api_url'];
			$logData['api_request_type'] = config('constants/apiType.REQUEST');
			$logData['api_data'] = $payLoad;
			$logData['api_status_message'] = config('journey/http-status.error.message');
			$logData['api_status_code'] = config('journey/http-status.error.code');
			$apiLogRepo = new ApiLogRepository();
			$apiLogRepo->save($logData);
			if (in_array(env('APP_ENV'),  config('constants/smsAndEmailTemplateCode.EXCEPTION_ENVIRONMENT'))) {
				$this->exceptionMailTrigger($e->getMessage(), $payLoad['api_url']);
			}
			return $resData;
		} catch (ConnectException $e) {
			Log::info("connection__Exceptions " . $payLoad['api_url'] . " " . $e->getMessage() . "\n");
			$resData = config('journey/http-status.timeout.message');
			// prepare log data.
			$logData['api_source'] = $headers['X-Api-Source'];
			$logData['api_source_page'] = $headers['X-Api-Source-Page'];
			$logData['api_type'] = $headers['X-Api-Type'];
			$logData['api_header'] = $headers;
			$logData['api_url'] = $payLoad['api_url'];
			$logData['api_request_type'] = config('constants/apiType.REQUEST');
			$logData['api_data'] = $payLoad;
			$logData['api_status_message'] = config('journey/http-status.timeout.message');
			$logData['api_status_code'] = config('journey/http-status.timeout.code');
			$apiLogRepo = new ApiLogRepository();
			$apiLogRepo->save($logData);
			if (in_array(env('APP_ENV'),  config('constants/smsAndEmailTemplateCode.EXCEPTION_ENVIRONMENT'))) {
				$this->exceptionMailTrigger($e->getMessage(), $logData['api_url']);
			}
			return $resData;
		}
	}
	public function aesEncryption($request)
	{
		$jsonData = (json_encode($request));
		$str =  $jsonData;
		$key = env('AES_KEY');
		$method_aes = env('AES_METHOD');
		$iv = env('AES_IV');
		$cipher = openssl_encrypt($str, $method_aes, $key, OPENSSL_RAW_DATA, $iv);
		return base64_encode($cipher);
	}

	public function coreAuthTokenApiCall()
	{
		try {
			$apiUrl = env('CORE_API_URL') . 'WebSiteAuthToken';
			// prepare log data.
			$logData['api_source'] = config('constants/apiSource.CORE');
			$logData['api_source_page'] = config('constants/apiSourcePage.MASTER_DATA_IMPORT');
			$logData['api_type'] = config('constants/apiType.CORE_AUTH_TOKEN');
			$logData['api_url'] = $apiUrl;
			$logData['api_request_type'] = config('constants/apiType.REQUEST');
			$logData['api_data'] = null;
			$logData['api_status_message'] = config('journey/http-status.success.message');
			$logData['api_status_code'] = config('journey/http-status.success.code');
			$apiLogRepo = new ApiLogRepository();
			$apiLogRepo->save($logData);
			$client = new Client();
			$response = $client->request('GET', $apiUrl, [
				'headers' => [
					'Accept' => 'application/json',
				],
				'timeout' => env('API_TIMEOUT'),
			]);
			$body = $response->getBody()->getContents();
			$logData['api_source'] = config('constants/apiSource.CORE');
			$logData['api_source_page'] = config('constants/apiSourcePage.MASTER_DATA_IMPORT');
			$logData['api_type'] = config('constants/apiType.CORE_AUTH_TOKEN');
			$logData['api_url'] = $apiUrl;
			$logData['api_request_type'] = config('constants/apiType.RESPONSE');
			$logData['api_data'] = $body;
			$logData['api_status_message'] = config('journey/http-status.success.message');
			$logData['api_status_code'] = config('journey/http-status.success.code');
			$apiLogRepo->save($logData);
			return $body;
		} catch (HttpClientException | ServerException $e) {
			Log::info("Api__Exceptions coreAuthTokenApiCall " . env('CORE_API_URL') . 'WebSiteAuthToken' . " " . $e->getMessage() . "\n");
			$resData = 'Error:Contact Administator';
			// prepare log data.
			$logData['api_source'] = config('constants/apiSource.CORE');
			$logData['api_source_page'] = config('constants/apiSourcePage.MASTER_DATA_IMPORT');
			$logData['api_type'] =  config('constants/apiType.CORE_AUTH_TOKEN');
			$logData['api_url'] = env('CORE_API_URL') . 'WebSiteAuthToken';
			$logData['api_request_type'] = config('constants/apiType.REQUEST');
			$logData['api_data'] = null;
			$logData['api_status_message'] = config('journey/http-status.error.message');
			$logData['api_status_code'] = config('journey/http-status.error.code');
			$apiLogRepo = new ApiLogRepository();
			$apiLogRepo->save($logData);
			return $resData;
		} catch (ConnectException $e) {
			Log::info("connection__Exceptions coreAuthTokenApiCall " . env('CORE_API_URL') . 'WebSiteAuthToken' . " " . $e->getMessage() . "\n");
			$resData = config('journey/http-status.timeout.message');
			// prepare log data.
			$logData['api_source'] = config('constants/apiSource.CORE');
			$logData['api_source_page'] = config('constants/apiType.CORE_TO_JOURNEY');
			$logData['api_type'] = config('constants/apiSourcePage.MASTER_DATA_IMPORT');
			$logData['api_url'] = env('CORE_API_URL') . 'WebSiteAuthToken';
			$logData['api_request_type'] = config('constants/apiType.REQUEST');
			$logData['api_data'] = null;
			$logData['api_status_message'] = config('journey/http-status.timeout.message');
			$logData['api_status_code'] = config('journey/http-status.timeout.code');
			$apiLogRepo = new ApiLogRepository();
			$apiLogRepo->save($logData);
			return $resData;
		}
	}

	/**
	 * Send Email and SMS method
	 *
	 * @param $payload
	 * @return mixed
	 */
	public function sendEmailWithSMS($payLoad)
	{
		try {
			$baseURL = env('WEBSITE_URL');
			$shortenedUrl = [];
			$payLoad['redirect_url'] = $fullURL = $baseURL . $payLoad['url'];
			if ($payLoad['is_short_url_required'] === true) {
				$shortenedUrl = $this->shortURL($fullURL,  $payLoad);
				if (!isset($shortenedUrl['shorturl']) && empty($shortenedUrl['status'])) {
					return $this->responseJson(
						config('journey/http-status.bad-request.status'),
						config('journey/http-status.bad-request.message'),
						config('journey/http-status.bad-request.code'),
						[]
					);
				}
			}

			$reqData['username'] = $payLoad['user_name'];
			$reqData['password'] = $payLoad['password'];
			$reqData['contactNumber'] = $this->contactNumberCheck($payLoad['mobile_number']);
			$reqData['message'] = $this->getTemplateMessage(
				$payLoad['sms_template_handle'],
				$payLoad['app_data'],
				$shortenedUrl,
				$payLoad
			);
			$personalRepo = new PersonalDetailRepository();
			$personalDt['lead_id'] = $payLoad['app_data']['lead_id'];
			$personalDt['quote_id'] = $payLoad['app_data']['quote_id'];
			$personalData = $personalRepo->getPersonalData($personalDt);
			$payLoad['unsubscribe'] = 0;
			if ($personalData && $personalData->unsubscribe) {
				$payLoad['unsubscribe'] = $personalData->unsubscribe;
			}
			if ($payLoad['is_email_required'] && $payLoad['unsubscribe'] == 0) {
				$this->sendEmail($payLoad);
			}
			$apiUrl = sprintf(
				"%susername=%s&password=%s&to=%s&message=%s",
				config('journey/sms.request_otp_url'),
				$reqData['username'],
				$reqData['password'],
				$reqData['contactNumber'],
				$reqData['message'],
			);
			$method = 'GET';
			$payLoad['data'] = $payLoad['api_data'];
			$payLoad['api_url'] = $apiUrl;
			$payLoad['method'] = $method;

			$requestData['api_type'] = $payLoad['api_type'] ?? null;
			$requestData['api_source_page'] = $payLoad['api_source'] ?? null;
			$requestData['api_source'] = $payLoad['type'] ?? null;
			$requestData['sms_template_type'] =  $payLoad['sms_template_handle'] == 'payment-l1' ? 'payment' : $payLoad['sms_template_handle'] ?? null;
			$requestData['payment_amount'] = $payLoad['payment_amount'] ?? null;
			$requestData['email_template_handle'] = $payLoad['email_template_handle'] ?? null;
			$requestData['payment_refence'] = $payLoad['payment_refence'] ?? null;
			$apiSourceValue = $payLoad['api_source'] ?? null;
			$requestData['utm_source'] = $apiSourceValue == 'CC_QUOTE_INFO_PAGE' ? 'SHFL_CC_INFO' : $payLoad['api_source'];
			$requestData['mobile_number'] = $payLoad['mobile_number'] ?? null;
			$requestData['email'] = $payLoad['email'] ?? null;
			$requestData['message'] = $reqData['message'] ?? null;

			// insert into sms log
			$smsLog['mobile_number'] = $reqData['contactNumber'] ?? null;
			$smsLog['quote_id'] =  $payLoad['app_data']['quote_id'] ?? null;
			$smsLog['cc_quote_id'] =  $payLoad['app_data']['cc_quote_id'] ?? null;
			$smsLog['master_product_id'] = $payLoad['app_data']['master_origin_product_id'] ?? null;
			$smsLog['source'] = $payLoad['type'] ?? null;
			$smsLog['source_page'] = $payLoad['api_source'] ?? null;
			$smsLog['api_type'] = $payLoad['api_type'] ?? null;
			$smsTemplate = $payLoad['sms_template_handle'] ?? null;
			$smsLog['sms_template_type'] =  $this->convertTemplateName($smsTemplate);
			$smsLog['request'] = $requestData ?? null;
			$smsLog['response'] = null;
			$smsLog['is_email_sent'] = $payLoad['is_email_required'] && $payLoad['unsubscribe'] == 0 ? 1 : 0;
			$smsRepo = new SmsLogRepository();
			$smsRepo->save($smsLog);

			$apiResponse = $this->coreClientApiCall($payLoad, [
				'Content-Type' => 'application/json',
				'X-Api-Source' => $payLoad['type'],
				'X-Api-Source-Page' => $payLoad['api_source'],
				'X-Api-Type' => $payLoad['api_type']
			]);
			$reqData['mobile_number'] = $reqData['contactNumber'];
			$reqData['quote_id'] = $payLoad['app_data']['quote_id'];
			$reqData['cc_quote_id'] = $payLoad['app_data']['cc_quote_id'];
			$lastInsertRecord = $smsRepo->getLastSmsLog($reqData);
			if ($lastInsertRecord) {
				$reqData['id'] =  $lastInsertRecord->_id;
				$reqData['response'] = $apiResponse ?? null;
				$smsRepo->updateSmsLog($reqData);
			}
			return $apiResponse;
		} catch (Throwable | HttpClientException $throwable) {
			Log::info("sendEmailWithSMS " . $throwable->__toString());
		}
	}

	/**
	 * convert template name
	 *
	 * @param $templateName
	 * @return mixed
	 */

	public function convertTemplateName($templateName)
	{
		try {
			$convertedSMSTemplateName  = $templateName;
			if ($templateName != null && $templateName == 'payment-l1') {
				$convertedSMSTemplateName = "payment";
			}
			return $convertedSMSTemplateName;
		} catch (Throwable | HttpClientException $throwable) {
			Log::info("convertTemplateName" . $throwable->__toString());
		}
	}

	/**
	 * Get Message Content based on Template
	 *
	 * @param $templateHandle, $appData, $fullURL, $payLoad
	 * @return mixed
	 */
	public function getTemplateMessage($templateHandle, $appData, $fullURL, $payLoad)
	{
		try {
			switch ($templateHandle) {
				case 'offer-details':
					$template = 'Congrats! You qualify for a ' . $appData['product_name'] . ' upto Rs.' . $appData['offer_amount'] . '.Contact 1800-102-4345 or visit our branch to apply. Download voucher ' . $fullURL['shorturl'] . ' T&C apply - Shriram Housing Finance Ltd.';
					break;
				case 'payment-l1':
					$template = 'Dear Customer,`Shriram Housing Finance Limited is requesting Rs. ' . $payLoad['payment_amount'] . ' to process your loan with reference ID: ' . $appData['quote_id'] . '. Kindly make the payment to proceed further.`Thanks, `Shriram Housing Finance';
					break;
				case 'document-upload':
					$template = 'Dear Customer,`Kindly complete your Shriram Housing Finance Limited Loan application with Ref. ID ' . $appData['quote_id'] . ' by uploading your documents. Click the link ' . $fullURL['shorturl'] . ' to proceed further.`Thanks,`Shriram Housing Finance';
					break;
				case 'payment-success':
					$template =
						'Success! Payment of Rs ' . $payLoad['payment_amount'] . ' completed. Reference: ' . $payLoad['payment_refence'] . '. Access your In-Principal Sanction Letter by clicking ' . $fullURL['shorturl'] . '. Thank you for choosing us - Shriram Housing Finance Ltd.';
					break;
				case 'drop-off':
					$template = 'Dear Customer, `You are just a few steps away from securing your ' . $appData['product_name'] . '. Complete your application here ' . $fullURL['shorturl'];
					break;
				default:
					$template = 'no-handle-found';
					break;
			}
			return $template;
		} catch (Throwable | HttpClientException $throwable) {
			Log::info("getTemplateMessage " . $throwable->__toString());
		}
	}

	/**
	 * Generate Short URL
	 *
	 * @param $url, $payload
	 * @return mixed
	 */

	public function shortURL($url,  $payLoad)
	{
		try {
			$shortUrl = config('constants/apiType.urlShortenerLink') . "apikey=" . config('constants/apiType.urlShortenerKey') . "&url=" . $url;
			$payLoad['api_type'] = 'shorten';
			$payLoad['api_url'] =  $shortUrl;
			$payLoad['data'] =  $shortUrl;
			$header['X-Api-Source'] = $payLoad['api_source'];
			$header['X-Api-Source-Page'] =  $payLoad['api_type'];
			$header['X-Api-Type'] =  $payLoad['type'];
			$payLoad['method'] = "GET";
			$apiResponse = $this->coreClientApiCall($payLoad, $header);

			if (empty($apiResponse) === false && empty($apiResponse['status']) ===  false && empty($apiResponse['shorturl'] === false)) {
				return $apiResponse;
			} else {
				return $this->responseJson(
					config('journey/http-status.error.status'),
					config('journey/http-status.error.message'),
					config('journey/http-status.failure.code'),
					[]
				);
			}
		} catch (Throwable | HttpClientException $throwable) {
			Log::info("shortURL " . $throwable->__toString());
		}
	}

	/**
	 * Send Email
	 *
	 * @param $payload
	 * @return mixed
	 */
	public function sendEmail($payLoad)
	{
		try {
			$appReq['quote_id'] = $payLoad['app_data']['quote_id'];
			$tomail = $payLoad['email'];
			$url = $payLoad['redirect_url'];
			$name = $payLoad['app_data']['name'];
			$module = $payLoad['email_template_handle'];
			$loanAmountArr = array($payLoad['app_data']['bre1_loan_amount'], $payLoad['app_data']['bre1_updated_loan_amount'], $payLoad['app_data']['bre2_loan_amount']);
			$payLoad['offer_amount'] = min($loanAmountArr);
			$payLoad['snippet'] = $this->getEmailSnippet($payLoad);
			$payLoad['unsubscribe_url'] = $this->unsubscibeUser($payLoad['app_data']);
			$subject = $this->getEmailSubject($payLoad);
			Mail::to($tomail)->send(new ExportData($payLoad, $url, $name, $module, $subject));
		} catch (Throwable | HttpClientException $throwable) {
			Log::info("sendEmail " . $throwable->__toString());
		}
	}

	/**
	 * Send Email
	 *
	 * @param    $payload
	 * @return mixed
	 */
	public function getEmailSubject($payLoad)
	{
		try {
			switch ($payLoad['email_template_handle']) {
				case 'payment-l1':
					$resSubject = "Complete Your Shriram Housing Finance Loan Application";
					break;
				case 'document-upload':
					$resSubject = "Urgent: Complete Your Shriram Housing Finance Application";
					break;
				case 'payment_success':
					$resSubject = "Exclusive Home Loan Offer Inside: Unlock Your Dream Home Today!/Exclusive Home Loan Offer from SHFL";
					break;
				case 'payment_fail':
					$resSubject = "Exclusive Home Loan Offer Inside: Unlock Your Dream Home Today!/Exclusive Home Loan Offer from SHFL ";
					break;
				case 'exception-email-core':
					$resSubject = "Exception mail-CORE";
					break;
				default:
					$resSubject = "Exclusive Home Loan Offer Inside: Unlock Your Dream Home Today!/Exclusive Home Loan Offer from SHFL";
					break;
			}
			return $resSubject;
		} catch (Throwable | HttpClientException $throwable) {
			Log::info("getEmailSubject " . $throwable->__toString());
		}
	}

	/**
	 * Get Email Snippet
	 *
	 * @param    $payload
	 * @return mixed
	 */
	public function getEmailSnippet($payLoad)
	{
		try {
			switch ($payLoad['email_template_handle']) {
				case 'payment-l1':
					$resSnippet = "Final Step: Payment confirmation required";
					break;
				case 'document-upload':
					$resSnippet = "Upload documents now for a swift loan approval";
					break;
				case 'exception-email-core':
					$resSnippet = "Exception mail raised for CORE";
					break;
				default:
					$resSnippet = "Unlock Your Dream Home Today!/Exclusive Home Loan Offer from SHFL";
					break;
			}
			return $resSnippet;
		} catch (Throwable | HttpClientException $throwable) {
			Log::info("getEmailSnippet " . $throwable->__toString());
		}
	}
	public function getProductNameUrl($code)
	{
		$productNameUrl = '';
		if ($code == 'HLNew' || $code == 'HLResale') {
			$productNameUrl = 'home-loan';
		} elseif ($code == 'HLBTTopup' || $code == 'HLBT' ||  $code == 'LAPBTTopup' || $code == 'LAPBT') {
			$productNameUrl = 'home-loan-balance-transfer';
		} elseif ($code == 'LAPResi') {
			$productNameUrl = 'loan-against-property-residential';
		} elseif ($code == 'LAPCom') {
			$productNameUrl = 'loan-against-property-commercial';
		} elseif ($code == 'HLBTExt' || $code == 'HLExt') {
			$productNameUrl = 'home-extension-loan';
		} elseif ($code == 'HLImp' || $code == 'HLBTImp') {
			$productNameUrl = 'home-loan-renovation';
		} elseif ($code == 'HLTopup' || $code == 'LAPTopup') {
			$productNameUrl = 'home-loan-top-up';
		} elseif ($code == 'HLPltConst' || $code == 'HLConst') {
			$productNameUrl = 'plot-plus-construction-loan';
		}
		return $productNameUrl;
	}

	public function unsubscibeUser($appData)
	{
		try {
			return env('WEBSITE_URL') . 'product-journey/unsubscribe?tok=' . $appData['auth_token'];
		} catch (Throwable | HttpClientException $throwable) {
			Log::info("unsubscibeUser " . $throwable->__toString());
		}
	}
	public function contactNumberCheck($mobileNumber)
	{
		return  empty($mobileNumber) === false ? sprintf("91%s", $mobileNumber) : "";
	}
	/**
	 * send exception email
	 */
	public function exceptionMailTrigger($errorMessage, $apiUrl)
	{
		try {
			Log::info("exception mail triggers");
			$exceptionCCMail = config('constants/smsAndEmailTemplateCode.EXCEPTION_MAIL_CC_RECEIPIENT');
			$ccArrayMail = explode(",", $exceptionCCMail);
			if (count($ccArrayMail) > 0) {
				$payLoad['email_template_handle'] = 'exception-email-core';
				$module = $payLoad['email_template_handle'];
				$payLoad['snippet'] = $this->getEmailSnippet($payLoad);
				$payLoad['error_message'] = $errorMessage;
				$payLoad['api_url'] = $apiUrl;
				$subject = $this->getEmailSubject($payLoad);
				Mail::to(config('constants/smsAndEmailTemplateCode.EXCEPTION_MAIL_RECEIPIENT'))->cc($ccArrayMail)->send(new ExportData($payLoad, '', '', $module, $subject));
			}
		} catch (Throwable | HttpClientException $throwable) {
			Log::info("CoreTrait exceptionMailTrigger " . $throwable->__toString());
		}
	}
}
