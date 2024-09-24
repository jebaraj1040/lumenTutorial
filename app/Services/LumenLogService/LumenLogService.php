<?php

namespace App\Services\LumenLogService;

use Throwable;
use Illuminate\Http\Request;
use App\Services\Service;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use App\Utils\CrmTrait;



class LumenLogService extends Service
{
    use CrmTrait;
    public function readLumenLog(Request $request)
    {
        try {
            $getDate = date("Y-m-d", strtotime($request['toDate']));
            $lumenpath = storage_path('logs/') . "lumen-" . $getDate . ".log";
            $rowCount = isset($request['rowCount']) ? $request['rowCount'] : 100;
            $lumenData = `tail -n $rowCount $lumenpath | tac`;
            if ($lumenData) {
                $returnLumenData =  $lumenData;
                echo json_encode($returnLumenData);
                return;
            }
        } catch (Throwable   | ClientException $throwable) {
            throw new Throwable(
                Log::info("Service : Lumen Log Service , Method : readLumenLog : %s", $throwable->__toString())
            );
        }
    }
    public function exportLumenLog(Request $request)
    {
        try {
            $getDate = date("Y-m-d", strtotime($request['toDate']));
            $filePath = storage_path('logs/') . "lumen-" . $getDate . ".log";
            $fileName = "Lumen-Logs-" . $getDate;
            if (!file_exists($filePath)) {
                return response()->json(['error' => 'File not found'], 404);
            }
            $fileContent = file_get_contents($filePath);
            $blob = new \stdClass();
            $blob->data = base64_encode($fileContent); // Encode file content as base64
            return response()->json([
                'blob' => $blob,
                'fileName' => $fileName
            ]);
        } catch (Throwable   | ClientException $throwable) {
            throw new Throwable(
                Log::info("Service : Lumen Log Service , Method : exportLumenLog : %s", $throwable->__toString())
            );
        }
    }
}
