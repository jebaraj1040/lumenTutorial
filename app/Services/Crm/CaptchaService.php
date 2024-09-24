<?php

namespace App\Services\Crm;

use Carbon\Carbon;
use App\Services\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Repositories\Crm\UserRepository;
use App\Utils\CrmTrait;

class CaptchaService extends Service
{
    use CrmTrait;
    protected $auth;
    public function generateRandomString($length)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $randomString;
    }
    public function generateCaptchaImage($text = 'good')
    {
        // Set the content-type
        $width  = 200;
        $height = 30;
        // Create the image
        $im = imagecreatetruecolor($width, $height);

        // Create some colors
        $white  = imagecolorallocate($im, 255, 255, 255);
        $grey   = imagecolorallocate($im, 128, 128, 128);
        $black  = imagecolorallocate($im, 0, 0, 0);
        imagefilledrectangle($im, 0, 0, 399, 29, $white);

        //ADD NOISE - DRAW background squares
        $squareCount = 6;
        for ($i = 0; $i < $squareCount; $i++) {
            $cx = random_int(0, $width);
            $cy = (int)random_int(0, $width / 2);
            $h  = $cy + (int)random_int(0, $height / 5);
            $w  = $cx + (int)random_int($width / 3, $width);
            imagefilledrectangle($im, $cx, $cy, $w, $h, $white);
        }

        //ADD NOISE - DRAW ELLIPSES
        $ellipseCount = 5;
        for ($i = 0; $i < $ellipseCount; $i++) {
            $cx = (int)random_int(-1 * ($width / 2), $width + ($width / 2));
            $cy = (int)random_int(-1 * ($height / 2), $height + ($height / 2));
            $h  = (int)random_int($height / 2, 2 * $height);
            $w  = (int)random_int($width / 2, 2 * $width);
            imageellipse($im, $cx, $cy, $w, $h, $grey);
        }

        // Replace path by your own font path
        $font = base_path('public/fonts/open-sans/OpenSans-Regular.ttf');

        // Add some shadow to the text
        imagettftext($im, 20, 0, 11, 21, $grey, $font, $text);

        // Add the text
        imagettftext($im, 20, 0, 10, 20, $black, $font, $text);

        // Using imagepng() results in clearer text compared with imagejpeg()
        $folderPath = app()->basePath() . "/storage/captcha";

        $requestId = $this->generateRandomString(10);
        imagepng($im, $folderPath . "/$requestId.png");
        $imageBase64 = base64_encode(file_get_contents($folderPath . '/' . $requestId . '.png'));
        return ['imageBase64' => $imageBase64, 'requestId' => $requestId];
    }

    public function generateCaptcha(UserRepository $userRepo)
    {
        $length = 6;
        $dateTime = Carbon::now();
        $randomString = $this->generateRandomString($length);
        $response = [];
        $getCaptcha = $this->generateCaptchaImage($randomString);
        if ($getCaptcha['requestId']) {
            $response = [
                'request_id' => $getCaptcha['requestId'],
                'captcha' => $getCaptcha['imageBase64'],
            ];
            $data = [
                'request_id' => $getCaptcha['requestId'],
                'captcha' => $randomString,
                'created_at' => $dateTime,
                'is_expired' => 0
            ];
            $userRepo->captchaLog($data);
        }
        $response['msg'] = "Captcha genereated successfully";
        return $this->successResponse($response);
    }
}
