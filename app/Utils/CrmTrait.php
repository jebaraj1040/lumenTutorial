<?php

namespace App\Utils;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Http\Client\HttpClientException;
use GuzzleHttp\Exception\ClientException;
use Throwable;

trait CrmTrait
{
    /**
     * Return Common Errors
     */
    public function commonErrorReponse()
    {
        return $this->responseJson(
            config('crm/http-status.error.status'),
            config('crm/http-status.error.message'),
            config('crm/http-status.error.code'),
            []
        );
    }
    /**
     * Return bad request Errors
     */
    protected function errorResponse($msg)
    {
        return $this->responseJson(
            config('crm/http-status.bad-request.status'),
            $msg,
            config('crm/http-status.bad-request.code'),
            []
        );
    }
    /**
     * Returns success response
     */
    protected function successResponse($data)
    {
        return $this->responseJson(
            config('crm/http-status.success.status'),
            $data['msg'] ?? $data,
            config('crm/http-status.success.code'),
            $data
        );
    }
    /**
     * Returns no data response
     */
    protected function noDataResponse()
    {
        return $this->responseJson(
            config('crm/http-status.no-data-found.status'),
            config('crm/http-status.no-data-found.message'),
            config('crm/http-status.no-data-found.code'),
            []
        );
    }
    /**
     * Returns Forbidden Response
     */
    protected function forbiddenResponse()
    {
        return $this->responseJson(
            config('crm/http-status.forbidden.status'),
            config('crm/http-status.forbidden.message'),
            config('crm/http-status.forbidden.code'),
            []
        );
    }
    /**
     * Process the file
     * @param $request
     */
    private function processFile($request, $userName, $path, $contentType)
    {
        $file = $request->file ?? null;
        $fileMime['type'] = $request->file->getMimeType();
        if ($file !== null && ($fileMime['type'] == "image/png" ||
            $fileMime['type'] == "image/jpg" || $fileMime['type'] == "image/jpeg")) {
            $fileMime['type'] = $file->getMimeType();
            $ext = strtolower($file->getClientOriginalExtension());
            if (in_array($ext, $contentType)) {
                $currtime = Carbon::now()->timestamp;
                $filename = $userName . "_" . $currtime;
                $file_name = $filename . "." . $file->extension();
                $file_Path = $path . $file_name;
                $s3_path = Storage::disk('s3')->put($file_Path, file_get_contents($file), 'public');
                if ($s3_path) {
                    return 'https://' . env('S3_BUCKET') . $file_Path;
                }
            } else {
                return $this->errorResponse('File type must be png, jpg, jpeg');
            }
        }
        return null;
    }
    /**
     * Create title for Excel sheet
     * @param $sheet
     * @param $title_array
     */
    private function createTitleForExcelSheet($sheet, $title_array)
    {
        $sheet->fromArray($title_array, null, 'A1');
    }
    /**
     * Save Excel file
     * @param $spreadsheet
     * @param $filename
     */
    private function saveExcelFileToS3($spreadsheet, $filename)
    {
        $writer = new Xlsx($spreadsheet);
        $date = Carbon::now();
        $timestamp = strtotime($date);
        $fileName =  $filename . $timestamp . ".xlsx";
        ob_start();
        $writer->save('php://output');
        $content = ob_get_contents();
        ob_end_clean();
        $pathNew = '/' . 'crm/export' . '/' . $fileName;
        Storage::disk('s3')->put($pathNew, $content, 'public');
        return Storage::disk('s3')->exists($pathNew) ? 'https://' . env('S3_BUCKET') . $pathNew : false;
    }
    /**
     * Get Non Empty Values
     * @param $data
     * @param $key
     */
    private function getNonEmptyValue($data, $key)
    {
        return empty($data[$key]) === false ? $data[$key] : "";
    }
    /**
     * Add data to Excel Sheet
     */
    private function addDataToExcelSheet($sheet, $postRecordArray)
    {
        $sheet->fromArray($postRecordArray, null, 'A2');
    }
    /**
     * Get Post Record
     * @param $results
     */
    private function getPostRecordArray($results): array
    {
        $postRecordArray = [];
        $data = json_decode($results, true);
        foreach ($data as &$record) {
            // Check if the record is an array and contains the 'created_at' field
            if (
                isset($record['created_at']['$date']['$numberLong'])
            ) {
                $timestampJson = $record['created_at']['$date']['$numberLong'];
                // Convert milliseconds to seconds
                $timestampInSeconds = $timestampJson / 1000;
                // Create Carbon object from timestamp in seconds
                $dateTime = Carbon::createFromTimestamp($timestampInSeconds);
                // Format the date as desired
                $formattedDate = $dateTime->toDateTimeString();
                // Update the 'created_at' field with the formatted date
                $record['created_at'] = $formattedDate;
            }
            // Convert all values in the record to JSON strings
            foreach ($record as $key => $val) {
                // Conditionally encode the value based on its type
                if ($key == 'id' || $key == '_id') {
                    unset($record[$key]);
                    continue;
                }
                $record[$key] = is_array($val) ? json_encode($val) : $val;
            }
            // Add the updated record to the new array
            $postRecordArray[] = $record;
        }
        return $postRecordArray;
    }
    /**
     * Get the title array for Excel sheet.
     *
     * @return array[]
     */

    private function getTitleArray($results)
    {
        $data = json_decode($results[0], true);
        unset($data['id'], $data['_id']);
        return array_keys($data);
    }

    public function exportExcel($mergedResults, $moduleName, $fileName): ?string
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $title_array = $this->getTitleArray($mergedResults);
            // Creating title for excel sheet
            $this->createTitleForExcelSheet($sheet, $title_array);
            $postRecordArray = $this->getPostRecordArray($mergedResults);
            $this->addDataToExcelSheet($sheet, $postRecordArray);
            return $this->saveExcelFileToS3($spreadsheet, $fileName);
        } catch (Throwable | ClientException $throwable) {
            Log::info("Service: " . $moduleName . "-Export, Method: exportExcel: %s"
                . $throwable->__toString());
        }
    }
    public function exportData($request, $repository, $datas)
    {
        try {
            $methodName = $datas['methodName'];
            $moduleName = $datas['moduleName'];
            $fileName = $datas['fileName'];
            $chunkSize = (int)env('EXPORT_EXCEL_LIMIT');
            $objreq = (object)$request;
            $count = $repository->{$methodName}($objreq, $chunkSize, 'count');
            $links = [];
            $numberOfFiles = ceil($count['totalLength'] / $chunkSize);
            $mergedResults = [];
            // for ($i = 0; $i < $numberOfFiles; $i++) {
            $offset = 0;
            $data = $repository->{$methodName}($request, $offset, 'download');
            // $results = array_merge($mergedResults, (array) $data['dataList']);
            // }
            $link = $this->exportExcel($data['dataList'], $moduleName, $fileName);
            $links[] = empty($link) === false ? $link : "";
            if (!empty($links)) {
                return $this->responseJson(
                    config('crm/http-status.success.status'),
                    'Export Success. Check your Downloads for the file.',
                    config('crm/http-status.success.code'),
                    [$links]
                );
            } else {
                Log::info("Data Not found");
                return false;
            }
        } catch (Throwable | ClientException $throwable) {
            Log::info("Service:" . $moduleName . "-Export, Method: exportData: %s" . $throwable->__toString());
        }
    }
    /**
     * convertFilterData
     *
     * @param $reqData
     * @param $column
     * @return mixed
     */
    public function convertFilterData($reqData, $column): mixed
    {
        try {
            $finalFilterData = array();
            if ($column == 'api_source') {
                foreach ($reqData as $filterData) {
                    if ($filterData != '') {
                        $filter['label'] = ucwords(str_replace("_", " ", strtolower($filterData)));
                        $filter['value'] = $filterData;
                        $finalFilterData[] = ($filter);
                    }
                }
            } else {
                foreach ($reqData as $filterData) {
                    if ($filterData != '') {
                        $labelcolumn = strtolower($filterData);
                        $filter['label'] = ucwords(str_replace("_", " ", $labelcolumn));
                        $filter['value'] = $filterData;
                        $finalFilterData[] = ($filter);
                    }
                }
            }
            return $finalFilterData;
        } catch (Throwable  | ClientException $throwable) {
            throw new Throwable(
                Log::info("Service : CrmTrait , Method : convertFilterData : %s", $throwable->__toString())
            );
        }
    }
    /**
     * filtered data
     * @param $constant
     */
    public function getFilterDatas($constant)
    {
        try {
            return config(sprintf("constants/%s", $constant));
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("CrmTrait   getFilterDatas" . $throwable->__toString());
        }
    }
    public function applyFilter($query, $request, $table = null)
    {
        if (
            empty($request->fromDate === false) &&  $request->fromDate != 'null' &&
            empty($request->toDate === false) && $request->toDate != 'null'
        ) {
            $fromDate = Carbon::createFromFormat('d-m-Y', $request->fromDate);
            $confromDate = $fromDate->format('Y-m-d 00:00:00');
            if (empty($request->toDate) === false) {
                $toDate = Carbon::createFromFormat('d-m-Y', $request->toDate);
                $conToDate = $toDate->format('Y-m-d 23:59:59');
            }

            $query->whereBetween(
                'created_at',
                array(
                    Carbon::createFromDate($confromDate),
                    Carbon::createFromDate($conToDate)
                )
            );
        }
        if (empty($request) === false && empty($request->sourcePage) === false && $table != 'websubmission' && $request->type != 'SMS_LOG') {
            $query->where('api_source_page', 'LIKE', '%' . $request->sourcePage . '%');
        }

        if (empty($request) === false && empty($request->sourcePage) === false && $table != 'websubmission' && $request->type == 'SMS_LOG') {
            $query->where('source_page', 'LIKE', '%' . $request->sourcePage . '%');
        }

        if (empty($request) === false && empty($request->source) === false) {
            $query->where('api_source', 'LIKE', '%' . $request->source . '%');
        }
        if (empty($request) === false && empty($request->us_source) === false) {
            $query->where('source', 'LIKE', '%' . $request->us_source . '%');
        }
        if (empty($request) === false && empty($request->apiType) === false) {
            $query->where('api_type', 'LIKE', '%' . $request->apiType . '%');
        }
        if (empty($request) === false && empty($request->requestType) === false) {
            $query->where('api_request_type', 'LIKE', '%' . $request->requestType . '%');
        }
        if (isset($request->productId) && $request->productId != '' && $request->productId != 'null') {
            $query->where('master_product_id', $request->productId);
        }
        if (isset($request->productStepId) && $request->productStepId != '' && $request->productStepId != 'null') {
            $query->where('master_product_step_id', $request->productStepId);
        }
        if ($table != null) {
            if (isset($request->leadId) && $request->leadId != '' && $request->leadId != 'null') {
                $query->where('id', $request->leadId);
            }
        } else {
            if (isset($request->leadId) && $request->leadId != '' && $request->leadId != 'null') {
                $query->where('lead_id', $request->leadId);
            }
        }
        if (isset($request->quoteId) && $request->quoteId != '' && $request->quoteId != 'null') {
            $query->where('quote_id', $request->quoteId);
        }

        return $query;
    }
}
