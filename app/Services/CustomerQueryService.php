<?php

namespace App\Services;

use App\Services\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Client\HttpClientException;
use App\Repositories\CustomerQueryRepository;
use Throwable;
use Illuminate\Support\Facades\Log;
use App\Utils\CrmTrait;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use SimpleXMLElement;
use Carbon\Carbon;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use App\Repositories\ApiLogRepository;

define('WSU_URL', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd');
define('SOAP', 'http://schemas.xmlsoap.org/soap/envelope/');
define('WSA', 'http://schemas.xmlsoap.org/ws/2004/08/addressing');
define('WSSE', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd');
define('API_SOURCE_WEB', config('constants/apiSource.WEB'));
define('API_TYPE_REQUEST', config('constants/apiType.REQUEST'));
define('API_SOURCE_PAGE_SERVICE', config('constants/apiSourcePage.SERVICE_REQUEST'));
define('API_STATUS_SUCCESS_CODE', config('journey/http-status.success.code'));
define('API_STATUS_SUCCESS_MESSAGE', config('journey/http-status.success.message'));
define('API_TYPE_RESOLVE_CONTACT', config('constants/apiType.TALISMA_RESOLVE_CONTACT'));
define('API_TYPE_RESPONSE', config('constants/apiType.RESPONSE'));
define('API_STATUS_ERROR_CODE', config('journey/http-status.error.code'));
define('API_STATUS_ERROR_MESSAGE', config('journey/http-status.error.message'));
define('API_TYPE_CREATE_CONTACT', config('constants/apiType.TALISMA_CREATE_INTERACTION'));
class CustomerQueryService extends Service
{
    use CrmTrait;
    /**
     * Customer Query.
     *
     * @param  Request $request
     * @return
     */
    public function saveCustomerQuery(Request $request, CustomerQueryRepository $customerQueryRepo)
    {
        try {
            $nameRegex = 'regex:/^[a-zA-Z]+(?:\s+[a-zA-Z]+)*$/';
            $rules = [
                "name" => ["required", $nameRegex],
                "email_id" => "required|email",
                "mobile_number" => "required|numeric|digits:10",
                "state" => ["required",   $nameRegex],
                "city" => ["required",   $nameRegex],
                "feedback" => "required",
            ];
            $validator = $this->validator($request->all(), $rules);
            if ($validator !== false) {
                return $validator;
            }
            $queryData['name'] = $request->name;
            $queryData['email_id'] = $request->email_id;
            $queryData['mobile_number'] = $request->mobile_number;
            $queryData['city'] = $request->city;
            $queryData['state'] = $request->state;
            $queryData['subject'] = $request->subject;
            $queryData['feedback'] = $request->feedback;
            $queryData["api_source"] = $request->header('X-Api-Source');
            $queryData["api_source_page"] = $request->header('X-Api-Source-Page');
            $queryData["api_type"] = $request->header('X-Api-Type');
            $queryData["api_header"] = $request->header();
            $queryData["api_url"] = $request->url();
            $queryData["api_request_type"] = API_TYPE_REQUEST;
            $queryData["api_data"] = $request;
            $queryData["api_status_code"] = API_STATUS_SUCCESS_CODE;
            $queryData["api_status_message"] = API_STATUS_SUCCESS_MESSAGE;
            $customerFeedback = $customerQueryRepo->save($queryData);
            if ($customerFeedback) {
                $this->resolveContact($request);
                return $this->responseJson(
                    config('journey/http-status.success.status'),
                    API_STATUS_SUCCESS_MESSAGE,
                    API_STATUS_SUCCESS_CODE,
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
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("CustomerQueryService saveCustomerQuery" . $throwable->__toString());
        }
    }
    public function getquerylist(Request $request, CustomerQueryRepository $customerQueryRepo): mixed
    {
        try {
            $customerQuery = $customerQueryRepo->getLog($request);
            return $this->responseJson(
                config('crm/http-status.success.status'),
                config('crm/http-status.success.message'),
                config('crm/http-status.success.code'),
                $customerQuery
            );
        } catch (Throwable   | HttpClientException $throwable) {
            throw new Throwable(
                Log::info("Service : LogService , Method : getcustomerQueryList : %s", $throwable->__toString())
            );
        }
    }
    public function exportLog(Request $request)
    {
        try {
            $rules = [
                "fromDate" => "required",
                "toDate"  => "required",
            ];
            $validator = $this->validator($request->all(), $rules);
            if ($validator !== false) {
                return $validator;
            }
            $repository = new CustomerQueryRepository();
            $datas['methodName'] = 'getLog';
            $datas['fileName'] = 'Query-Log-Report-';
            $datas['moduleName'] = 'Query-Log';
            return $this->exportData($request, $repository, $datas);
        } catch (Throwable  | ClientException $throwable) {
            Log::info("QueryLogService  exportLog" . $throwable->__toString());
            return $this->responseJson(
                config('crm/http-status.error.status'),
                config('crm/http-status.error.message'),
                config('crm/http-status.error.code'),
                []
            );
        }
    }
    public function getFilterData()
    {
        try {
            $apiSource = $this->getFilterDatas('apiSource');
            $apiSourcePage = $this->getFilterDatas('apiSourcePage');
            $apiType = $this->getFilterDatas('apiType');
            $filterList['api_source'] =  $this->convertFilterData($apiSource, 'api_source');
            $filterList['api_source_page'] =  $this->convertFilterData($apiSourcePage, 'api_source_page');
            $filterList['api_type'] =  $this->convertFilterData($apiType, 'api_type');
            return $this->responseJson(
                config('crm/http-status.success.status'),
                config('crm/http-status.success.message'),
                config('crm/http-status.success.code'),
                $filterList
            );
        } catch (Throwable  | ClientException $throwable) {
            throw new Throwable(
                Log::info("Service : QueryLogService , Method : getFilterData : %s", $throwable->__toString())
            );
        }
    }
    /**
     * resolve contact
     *
     */
    public function resolveContact($request)
    {
        try {
            $reolveLog = new CustomerQueryRepository();
            $xml = new \DOMDocument('1.0', 'UTF-8');
            // create envelope
            $envelope = $this->setEnvelope($xml);
            $xml->appendChild($envelope);
            // create header
            $header = $this->setHeader($xml, 'resolve');
            $envelope->appendChild($header);

            // create body
            $body = $xml->createElement("soap:Body");
            $envelope->appendChild($body);
            $requestBody = $xml->createElement("ResolveContact");
            $requestBody->setAttribute("xmlns", "http://www.talisma.com/");
            // body request
            $bodyRequest = [
                'noCheckForDuplicateEmail' => 'true',
                'contactPropData' => [
                    'PropertyInfo' => [
                        'propertyID' =>  config('constants/customerQuery.MOBILE_NUMBER_PROP_ID'),
                        'propValue' => $request->mobile_number,
                        'rowID' => config('constants/customerQuery.ROW_ID'),
                        'relJoinID' => config('constants/customerQuery.REL_JOIN_ID'),
                        'propValAttrs' => ''
                    ]
                ]
            ];
            foreach ($bodyRequest as $bKey => $bValue) {
                $this->buildXml($xml, $requestBody, [$bKey => $bValue]);
            }
            $body->appendChild($requestBody);
            $xml->preserveWhiteSpace = true;
            $xml->formatOutput = true;
            $soapXml = $xml->saveXML();
            $url = env('TALISMA_URL') . 'ContactiService130512/Contact.AsmX';
            $talismaLog['mobile_number'] = $request->mobile_number;
            $talismaLog['api_source'] = API_SOURCE_WEB;
            $talismaLog['api_source_page'] = API_SOURCE_PAGE_SERVICE;
            $talismaLog['api_type'] = API_TYPE_RESOLVE_CONTACT;
            $talismaLog['api_url'] = $url;
            $talismaLog['api_request_type'] = API_TYPE_REQUEST;
            $talismaLog['api_data'] = $bodyRequest;
            $talismaLog['api_status_code'] =  API_STATUS_SUCCESS_CODE;
            $talismaLog['api_status_message'] =  API_STATUS_SUCCESS_MESSAGE;
            $reolveLog->resolveContactSave($talismaLog);
            $response = $this->clientApiCall($url, $soapXml, API_TYPE_RESOLVE_CONTACT);
            if (gettype($response) == 'string') {
                $xml = new SimpleXMLElement($response);
                $xml->registerXPathNamespace('soap', SOAP);
                $xml->registerXPathNamespace('wsa', WSA);
                $xml->registerXPathNamespace('wsse', WSSE);
                $xml->registerXPathNamespace('wsu', WSU_URL);
                $bodyContent = $xml->xpath('//soap:Body/*');
                $finalOutput = json_decode(json_encode($bodyContent), true);
                $talismaLog['mobile_number'] = $request->mobile_number;
                $talismaLog['api_source'] = API_SOURCE_WEB;
                $talismaLog['api_source_page'] = API_SOURCE_PAGE_SERVICE;
                $talismaLog['api_type'] = API_TYPE_RESOLVE_CONTACT;
                $talismaLog['api_url'] = $url;
                $talismaLog['api_request_type'] = API_TYPE_RESPONSE;
                $talismaLog['api_data'] = (object) $bodyContent;
                $talismaLog['api_status_code'] =  API_STATUS_SUCCESS_CODE;
                $talismaLog['api_status_message'] =  API_STATUS_SUCCESS_MESSAGE;
                $reolveLog->resolveContactSave($talismaLog);
                if (
                    isset($finalOutput[0])
                    && isset($finalOutput[0]['contactId']) && $finalOutput[0]['contactId'] != -1
                ) {
                    return $this->createInteraction($finalOutput[0]['contactId'], $request);
                }
            } else {
                $talismaLog['mobile_number'] = $request->mobile_number;
                $talismaLog['api_source'] = API_SOURCE_WEB;
                $talismaLog['api_source_page'] = API_SOURCE_PAGE_SERVICE;
                $talismaLog['api_type'] = API_TYPE_RESOLVE_CONTACT;
                $talismaLog['api_url'] = $url;
                $talismaLog['api_request_type'] = API_TYPE_RESPONSE;
                $talismaLog['api_data'] = $response;
                $talismaLog['api_status_code'] =   API_STATUS_ERROR_CODE;
                $talismaLog['api_status_message'] =  API_STATUS_ERROR_MESSAGE;
                $reolveLog->resolveContactSave($talismaLog);
            }
        } catch (Throwable | HttpClientException $throwable) {
            $talismaLog['mobile_number'] = $request->mobile_number;
            $talismaLog['api_source'] = API_SOURCE_WEB;
            $talismaLog['api_source_page'] = API_SOURCE_PAGE_SERVICE;
            $talismaLog['api_type'] = API_TYPE_RESOLVE_CONTACT;
            $talismaLog['api_url'] = $url;
            $talismaLog['api_request_type'] = API_TYPE_REQUEST;
            $talismaLog['api_data'] = $request->all();
            $talismaLog['api_status_code'] =   API_STATUS_ERROR_CODE;
            $talismaLog['api_status_message'] =  API_STATUS_ERROR_MESSAGE;
            $reolveLog->resolveContactSave($talismaLog);
            Log::info("CustomerQueryService -  resolveContact " . $throwable);
        }
    }
    /**
     * create interaction
     *
     */
    public function createInteraction($contactId, $request)
    {
        try {
            $createLog = new CustomerQueryRepository();
            $mytime = Carbon::now();
            $dateTime = explode(" ", $mytime);
            $createdTime = $dateTime[0] . 'T' . $dateTime[1] . 'Z';
            $requestData = [
                'contactId' => $contactId,
                'receivedAt' => $createdTime,
                'receivedByUserId' => config('constants/customerQuery.USER_ID'),
                'mediaId' => config('constants/customerQuery.MEDIA_ID'),
                'direction' => config('constants/customerQuery.DIRECTION'),
                'subject' => $request->subject,
                'teamId' => config('constants/customerQuery.TEAM_ID'),
                'contactMsg' => $request->feedback,
                'userMsg' => '',
                'propData' => [
                    'PropertyInfo' => [
                        'propertyID' => config('constants/customerQuery.STATE_ID'),
                        'propValue' => $request->state,
                        'rowID' => config('constants/customerQuery.ROW_ID'),
                        'relJoinID' => config('constants/customerQuery.REL_JOIN_ID'),
                        'propValAttrs' => ''
                    ]
                ],
                'updateReadOnly' => true,
                'ignoreMandatoryCheck' => true
            ];
            $xml = new \DOMDocument('1.0', 'UTF-8');
            // create envelope
            $envelope = $this->setEnvelope($xml);
            $xml->appendChild($envelope);
            // create header
            $header = $this->setHeader($xml, 'create');
            $envelope->appendChild($header);
            // create body
            $body = $xml->createElement("soap:Body");
            $envelope->appendChild($body);
            $requestDataElement = $xml->createElement("CreateInteraction");
            $requestDataElement->setAttribute("xmlns", "http://www.talisma.com/");
            foreach ($requestData as $bKey => $bValue) {
                $this->buildXml($xml, $requestDataElement, [$bKey => $bValue]);
            }
            $body->appendChild($requestDataElement);
            $xml->preserveWhiteSpace = true;
            $xml->formatOutput = true;
            $soapXml = $xml->saveXML();
            $additionalXmlData = $this->getCityXMLData($request->city);
            $position = strrpos($soapXml, '</propData>');
            $xmlString = substr_replace($soapXml, $additionalXmlData, $position, 0);
            $url = env('TALISMA_URL') . 'InteractioniService130512/Interaction.AsmX';
            $talismaLog['mobile_number'] = $request->mobile_number;
            $talismaLog['api_source'] = API_SOURCE_WEB;
            $talismaLog['api_source_page'] = API_SOURCE_PAGE_SERVICE;
            $talismaLog['api_type'] = API_TYPE_CREATE_CONTACT;
            $talismaLog['api_url'] = $url;
            $talismaLog['api_request_type'] = API_TYPE_REQUEST;
            $talismaLog['api_data'] = $requestData;
            $talismaLog['api_status_code'] =  API_STATUS_SUCCESS_CODE;
            $talismaLog['api_status_message'] =  API_STATUS_SUCCESS_MESSAGE;
            $createLog->createContactSave($talismaLog);
            Log::info("soap Request ", [$xmlString]);
            $response = $this->clientApiCall($url, $xmlString, API_TYPE_CREATE_CONTACT);
            Log::info("soap Response ", [$response]);
            if (gettype($response) == 'string') {
                $xml = new SimpleXMLElement($response);
                $xml->registerXPathNamespace('soap', SOAP);
                $xml->registerXPathNamespace('wsa', WSA);
                $xml->registerXPathNamespace('wsse', WSSE);
                $xml->registerXPathNamespace('wsu', WSU_URL);
                $bodyContent = $xml->xpath('//soap:Body/*');
                $talismaLog['mobile_number'] = $request->mobile_number;
                $talismaLog['api_source'] = API_SOURCE_WEB;
                $talismaLog['api_source_page'] = API_SOURCE_PAGE_SERVICE;
                $talismaLog['api_type'] = API_TYPE_CREATE_CONTACT;
                $talismaLog['api_url'] = $url;
                $talismaLog['api_request_type'] = API_TYPE_RESPONSE;
                $talismaLog['api_data'] = (object)$bodyContent;
                $talismaLog['api_status_code'] =  API_STATUS_SUCCESS_CODE;
                $talismaLog['api_status_message'] =  API_STATUS_SUCCESS_MESSAGE;
                $createLog->createContactSave($talismaLog);
                return json_decode(json_encode($bodyContent), true);
            } else {
                $talismaLog['mobile_number'] = $request->mobile_number;
                $talismaLog['api_source'] = API_SOURCE_WEB;
                $talismaLog['api_source_page'] = API_SOURCE_PAGE_SERVICE;
                $talismaLog['api_type'] = API_TYPE_CREATE_CONTACT;
                $talismaLog['api_url'] = $url;
                $talismaLog['api_request_type'] = API_TYPE_RESPONSE;
                $talismaLog['api_data'] = $response;
                $talismaLog['api_status_code'] =   API_STATUS_ERROR_CODE;
                $talismaLog['api_status_message'] =  API_STATUS_ERROR_MESSAGE;
                $createLog->createContactSave($talismaLog);
            }
        } catch (Throwable | HttpClientException $throwable) {
            $talismaLog['mobile_number'] = $request->mobile_number;
            $talismaLog['api_source'] = API_SOURCE_WEB;
            $talismaLog['api_source_page'] = API_SOURCE_PAGE_SERVICE;
            $talismaLog['api_type'] = API_TYPE_CREATE_CONTACT;
            $talismaLog['api_url'] = $url;
            $talismaLog['api_request_type'] = API_TYPE_REQUEST;
            $talismaLog['api_data'] = $request->all();
            $talismaLog['api_status_code'] =   API_STATUS_ERROR_CODE;
            $talismaLog['api_status_message'] =  API_STATUS_ERROR_MESSAGE;
            $createLog->resolveContactSave($talismaLog);
            Log::info("CustomerQueryService -  createInteraction " . $throwable);
        }
    }
    /**
     * set envelope for resolve and create
     *
     */
    public function setEnvelope($xml)
    {
        try {
            $envelope = $xml->createElementNS("http://schemas.xmlsoap.org/soap/envelope/", "soap:Envelope");
            $envelope->setAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
            $envelope->setAttribute("xmlns:xsd", "http://www.w3.org/2001/XMLSchema");
            $envelope->setAttribute("xmlns:wsa", "http://schemas.xmlsoap.org/ws/2004/08/addressing");
            $envelope->setAttribute(
                "xmlns:wsse",
                "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd"
            );
            $envelope->setAttribute(
                "xmlns:wsu",
                WSU_URL
            );
            return $envelope;
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("CustomerQueryService -  setEnvelope " . $throwable);
        }
    }
    /**
     * set header for resolve and create
     *
     */
    public function setHeader($xml, $action)
    {
        try {
            /*$mytime = Carbon::now()->subDay(1);
            $dateTime = explode(" ", $mytime);
            $createdTime = $dateTime[0] . 'T' . $dateTime[1] . 'Z';
            $futureDate = Carbon::createFromFormat('Y-m-d H:i:s', $mytime)->addMonth();
            $futureFormatedDate = $futureDate->format('Y-m-d H:i:s');
            $futureDateTime = explode(" ", $futureFormatedDate);
            $expiresTime = $futureDateTime[0] . 'T' . $futureDateTime[1] . 'Z';*/
            if ($action == 'resolve') {
                $url = env('TALISMA_URL') . 'ContactiService130512/Contact.AsmX';
                $headerUrl = "http://www.talisma.com/ResolveContact";
            } else {
                $url = env('TALISMA_URL') . 'InteractioniService130512/Interaction.AsmX';
                $headerUrl = "http://www.talisma.com/CreateInteraction";
            }
            $header = $xml->createElement("soap:Header");
            // create header child
            $headerSet1 = $xml->createElement("wsa:Action", $headerUrl);
            $header->appendChild($headerSet1);

            $headerSet2 = $xml->createElement("wsa:MessageID", "urn:uuid:560db3ec-f288-4971-9ff3-1d4c7ded9f88");
            $header->appendChild($headerSet2);

            $headerSet3 = $xml->createElement("wsa:ReplyTo");
            $header->appendChild($headerSet3);

            $headerSet4 = $xml->createElement(
                "wsa:Address",
                "http://schemas.xmlsoap.org/ws/2004/08/addressing/role/anonymous"
            );
            $headerSet3->appendChild($headerSet4);

            $headerSet5 = $xml->createElement("wsa:To", $url);
            $header->appendChild($headerSet5);

            $headerSet6 = $xml->createElement("wsse:Security");
            $headerSet6->setAttribute("soap:mustUnderstand", 1);
            $header->appendChild($headerSet6);

            /*  $headerSet7 = $xml->createElement("wsu:Timestamp");
            $headerSet7->setAttribute("wsu:Id", "Timestamp-6415016e-9d0c-4c09-8832-cf3ba84d18af");
            $headerSet6->appendChild($headerSet7);

            $headerSet8 = $xml->createElement(
                "wsu:Created",
                $createdTime
            );
            $headerSet7->appendChild($headerSet8);

            $headerSet9 = $xml->createElement(
                "wsu:Expires",
                $expiresTime
            );
            $headerSet7->appendChild($headerSet9);*/

            $headerSet10 = $xml->createElement("wsse:UsernameToken");
            $headerSet10->setAttribute(
                "xmlns:wsu",
                WSU_URL
            );
            $headerSet10->setAttribute(
                "wsu:Id",
                "SecurityToken-4f60806e-c940-45d2-a82f-9c654fcefd13"
            );
            $headerSet6->appendChild($headerSet10);

            $headerSet11 = $xml->createElement("wsse:Username", env('TALISMA_USER_NAME'));
            $headerSet10->appendChild($headerSet11);

            $headerSet12 = $xml->createElement("wsse:Password", env('TALISMA_PASSWORD'));
            $headerSet10->appendChild($headerSet12);
            /*$headerSet13 = $xml->createElement(
                "wsse:Nonce",
                "crTDTGVEppXvYj7PacS+HQ=="
            );
            $headerSet10->appendChild($headerSet13);

            $headerSet14 = $xml->createElement(
                "wsu:Created",
                "2015-07-25T10:50:54Z"
            );
            $headerSet10->appendChild($headerSet14);*/
            $headerSet15 = $xml->createElement("TalismaSessionkey");
            $headerSet10->appendChild($headerSet15);
            return $header;
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("CustomerQueryService -  setHeader " . $throwable);
        }
    }
    /**
     * set body for resolve and create
     *
     */
    public function buildXml($xml, $parent, $data)
    {
        try {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $element = $xml->createElement($key);
                    $this->buildXml($xml, $element, $value);
                    $parent->appendChild($element);
                } else {
                    $parent->appendChild($xml->createElement($key, $value));
                }
            }
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("CustomerQueryService -  buildXml " . $throwable);
        }
    }
    /**
     * city xml data
     *
     */
    public function getCityXMLData($city)
    {
        try {
            return  '<PropertyInfo>
            <propertyID>' . config('constants/customerQuery.CITY_ID') . '</propertyID>
            <propValue>' . $city . '</propValue>
            <rowID>' . config('constants/customerQuery.ROW_ID') . '</rowID>
            <relJoinID>' . config('constants/customerQuery.REL_JOIN_ID') . '</relJoinID>
            <propValAttrs/>
          </PropertyInfo>';
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("CustomerQueryService -  getCityXMLData " . $throwable);
        }
    }
    /**
     * call client api
     *
     */
    public function clientApiCall($url, $xmlData, $apiType)
    {
        try {
            $httpClient = new Client();
            $response = $httpClient->post(
                $url,
                [
                    RequestOptions::BODY => $xmlData,
                    RequestOptions::HEADERS =>  [
                        'Content-Type' => 'text/xml',
                    ],
                    RequestOptions::TIMEOUT => env('API_TIMEOUT'),
                ]
            );
            return $response->getBody()->getContents();
        } catch (HttpClientException | ServerException $e) {
            Log::info("Api__Exceptions clientApiCall " . $url . " " . $e->getMessage() . "\n");
            // prepare log data.
            $errorMessage['error'] = $e->getMessage();
            $logData['api_source'] = API_SOURCE_WEB;
            $logData['api_source_page'] = API_SOURCE_PAGE_SERVICE;
            $logData['api_type'] = $apiType;
            $logData['api_url'] = $url;
            $logData['api_request_type'] = API_TYPE_RESPONSE;
            $logData['api_data'] = $errorMessage;
            $logData['api_status_message'] = config('journey/http-status.error.message');
            $logData['api_status_code'] = config('journey/http-status.error.code');
            $apiLogRepo = new ApiLogRepository();
            $apiLogRepo->save($logData);
            return $this->responseJson(
                config('journey/http-status.error.status'),
                API_STATUS_ERROR_MESSAGE,
                API_STATUS_ERROR_CODE,
                $errorMessage
            );
        } catch (ConnectException $e) {
            Log::info("connection__Exceptions " . $url . " " . $e->getMessage() . "\n");
            // prepare log data.
            $errorMessage['error'] = $e->getMessage();
            $logData['api_source'] = API_SOURCE_WEB;
            $logData['api_source_page'] = API_SOURCE_PAGE_SERVICE;
            $logData['api_type'] = $apiType;
            $logData['api_url'] = $url;
            $logData['api_request_type'] = API_TYPE_RESPONSE;
            $logData['api_data'] = $errorMessage;
            $logData['api_status_message'] = config('journey/http-status.timeout.message');
            $logData['api_status_code'] = config('journey/http-status.timeout.code');
            $apiLogRepo = new ApiLogRepository();
            $apiLogRepo->save($logData);
            return $this->responseJson(
                config('journey/http-status.timeout.status'),
                config('journey/http-status.timeout.message'),
                config('journey/http-status.timeout.code'),
                $errorMessage
            );
        }
    }
}
