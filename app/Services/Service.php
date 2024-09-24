<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Laravel\Lumen\Routing\Controller as BaseController;

class Service extends BaseController
{
    public function responseJson(bool $status, string $message, int $statusCode, $data = [])
    {
        $response = [
            'status' => $status,
            'code' => $statusCode,
            'message' => $message,
            'data' => $data
        ];
        return response()->json($response, $response['code']);
    }

    public function validator(array $request, array $rules, $messages = [])
    {
        $validator = Validator::make($request, $rules, $messages);

        if ($validator->fails()) {
            $messages = $validator->messages();
            $messagesFormat = [];
            $errorMessage = "";
            foreach ($messages->toArray() as $key => $message) {
                $messagesFormat["errors"][] = ["attribute" => $key, "message" => $message[0]];
                $errorMessage = $message[0] . "," . $errorMessage;
                $messagesFormat["errorsMessage"] = substr_replace($errorMessage, "", -1);
            }

            return $this->responseJson(config('journey/http-status.bad-request.status'), config('journey/http-status.bad-request.message'), config('journey/http-status.bad-request.code'), $messagesFormat);
        }

        return false;
    }

    public function validPhoneData($mobile)
    {

        if (preg_match("/(.)\\1+/", $mobile)) {
            return response()->json(['status' => 0]);
        } elseif (preg_match("/.*0{9}.*/", $mobile)) {
            return response()->json(['status' => 0]);
        } elseif (preg_match('/^[789]\d{9}$/', $mobile)) {
            return response()->json(['status' => 1]);
        } else {
            return response()->json(['status' => 0]);
        }
    }

    public function invalidPhone($mobile)
    {
        $mobilearray = array("6000000000", "7000000000", "8000000000", "9000000000");
        if (in_array($mobile, $mobilearray)) {
            return response()->json(['status' => 0]);
        } else {
            return response()->json(['status' => 1]);
        }
    }

    public function isSpam($phone)
    {
        $json = $this->validPhoneData($phone);
        $json = json_decode($json->getContent(), true);
        $validstatus1 = $json['status'];
        $invalid = $this->invalidPhone($phone);
        $invalidstatus = json_decode($invalid->getContent(), true);
        $validstatus2 = $invalidstatus['status'];

        if ($validstatus1 == 0 || $validstatus2 == 0) {
            return true;
        } else {
            return false;
        }
    }
}
