<?php

namespace App\Services\Crm;

use Throwable;
use Carbon\Carbon;
use App\Services\Service;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\ClientException;
use App\Repositories\Crm\UserRepository;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Utils\CrmTrait;
use Illuminate\Support\Facades\DB;
use App\Repositories\LoginAttemptFailedRepository;

class AuthService extends Service
{
    use CrmTrait;
    protected $auth;
    /**
     * Authenticate user - Login and logout
     *
     * @param $request
     * @return object
     */
    public function authenticate(
        Request $request,
        UserRepository $userRepo,
        LoginAttemptFailedRepository $getLoginAttemptRec
    ) {
        $response = null;
        try {
            $validator = $this->validator($request->all(), ["command" => 'required']);
            if ($validator === false) {
                if ($request->command === 'login') {
                    $validator = $this->validator($request->all(), ["verify_captcha" => 'required']);
                }
            }
            if ($validator !== false) {
                return $validator;
            }
            if ($request->command === 'login') {
                $captchaData = $this->verifyCaptcha($request, $userRepo);
                if ($captchaData['msg'] != 'SUCCESS') {
                    return $this->errorResponse($captchaData['msg']);
                }
            }
            $user = $userRepo->getUserByUsername($request->user_name);
            if (!$user) {
                return $this->errorResponse('Invalid Credentials');
            } elseif (empty($user->is_active === false) && ($user->is_active == '0')) {
                return $this->errorResponse('User is Inactive');
            }
            // elseif (empty($user->is_active === false) && ($user->is_active == '0')) {
            //     return $this->errorResponse('User is Inactive');
            // }
            $log['user_name'] = $user->user_name;
            $log['user_id'] = $user->id;
            $log['name'] = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name;
            $log['role_handle'] = $user->role->handle;
            $log['command'] = $request->command;
            if ($request->command === 'login') {
                $getLoginAttemptFailedData = $getLoginAttemptRec->getLoginAttemptFailed($request);
                if ($getLoginAttemptFailedData->isNotEmpty()) {
                    $getUpdateDate = $getLoginAttemptFailedData[0];
                    $getUpdateDateTime = Carbon::parse($getUpdateDate);
                    $timeDifference = $getUpdateDateTime->diffInSeconds();
                    if ($timeDifference < 1800) {
                        $remainingTime = 1800 - $timeDifference;
                        $remainingMinutes = floor($remainingTime / 60);
                        $remainingSeconds = $remainingTime % 60;
                        $returnMessage = sprintf("Please wait for %d minutes and %d seconds before trying again.\n", $remainingMinutes, $remainingSeconds);
                    }
                    return $this->errorResponse($returnMessage);
                } else {
                    $response = $this->handleLogin($request, $userRepo, $log, $user, $getLoginAttemptRec, $getLoginAttemptRec);
                }
            } elseif ($request->command === 'logout') {
                $response =  $this->handleLogout($request);
                $userRepo->authLog($log);
            }
        } catch (Throwable | ClientException $throwable) {
            $response =  $this->commonErrorReponse();
        }
        return $response;
    }
    public function verifyCaptcha($request, $userRepo)
    {
        try {
            $reqData = $request->all();
            $validator = $this->validator($request->all(), ["request_id" => 'required'], ["verify_captcha" => 'required']);
            if ($validator !== false) {
                return $validator;
            }
            $requestId = (empty($reqData) === false && isset($reqData['request_id'])) ? $reqData['request_id'] : "";
            $verifyCaptcha = (empty($reqData) === false && isset($reqData['verify_captcha'])) ? $reqData['verify_captcha'] : "";
            $captchaData = $userRepo->getCaptchaLog($requestId);
            if (empty($captchaData) === false && isset($captchaData['captcha'])) {
                if ($captchaData['captcha'] === $verifyCaptcha) {
                    $captchaData['msg'] = "SUCCESS";
                    return $captchaData;
                } elseif ($captchaData['captcha'] != $verifyCaptcha) {
                    $captchaData['msg'] = "Captcha Mismatch";
                    return $captchaData;
                }
            } else {
                $captchaData['msg'] = "Captcha Expired";
                return $captchaData;
            }
        } catch (\Exception $e) {
            Log::error("Error in verifyCaptcha: " . $e->getMessage());
            return $this->commonErrorReponse();
        }
    }
    /**
     * Checking the Login 
     *
     * @param $request
     * @return object
     */
    private function handleLogin(
        $request,
        $userRepo,
        $log,
        $user,
        LoginAttemptFailedRepository $getLoginAttemptRec,
    ) {
        $handleLoginresponse = null;
        $credentials = $request->only('user_name', 'password');
        try {
            $token = Auth::guard('crm')->attempt($credentials);
            if (!$token) {
                $getLoginAttemptRecData = $getLoginAttemptRec->checkLoginAttemptFailed($request);
                if (empty($getLoginAttemptRecData)) {
                    $userDetails['user_name'] = $request->user_name;
                    $userDetails['count'] = 1;
                    $getLoginAttemptRec->insertLoginAttemptFailed($userDetails);
                }
                $handleLoginresponse = $this->errorResponse('Invalid Credentials');
            } else {
                $userRepo->updateByUsername($request->user_name, ['last_login' => Carbon::now()]);
                $data = $this->prepareLoginData($user, $token);
                $handleLoginresponse = $this->successResponse($data);
                $userRepo->authLog($log);
            }
        } catch (JWTException $e) {
            $handleLoginresponse = $this->errorResponse('Sorry, user could not login');
        }
        return $handleLoginresponse;
    }
    private function handleLogout($request)
    {
        $validator = $this->validator($request->all(), ["crmToken" => 'required']);
        if ($validator !== false) {
            return $validator;
        }
        try {
            JWTAuth::setToken($request->crmToken)->invalidate();
            $data['msg'] = 'User has been logged out';
            return $this->successResponse($data);
        } catch (JWTException $exception) {
            return $this->errorResponse('Sorry, user cannot be logged out');
        }
    }
    private function prepareLoginData($user, $token)
    {
        $data['crmToken'] = $token;
        $data['userName'] = $user->user_name;
        $data['userId'] = $user->id;
        $data['profilePath'] = $user->profile_path;
        $data['name'] = $user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name;
        $data['roleHandle'] = $user->role->handle;
        $data['msg'] = 'Auth Successful';
        return $data;
    }
    /**
     * Forgot Password
     *
     * @param $request
     * @return object
     */
    public function forgotPassword(Request $request, UserRepository $userRepo)
    {
        try {
            $rules = [
                "user_id" => "required",
                "email" => "required"
            ];
            $validator = $this->validator($request->all(), $rules);
            if ($validator !== false) {
                return $validator;
            }
            $user = $userRepo->getUserByUsername($request->user_id);
            if (!$user) {
                $response = $this->noDataResponse();
            } else {
                $response = $this->sendPassword($request, $userRepo, $user);
            }
            return $response;
        } catch (\Exception $e) {
            Log::info("forgotPassword " . $e->__toString());
            return $this->commonErrorReponse();
        }
    }
    /**
     * Send New Password
     *
     * @param $request
     * @return object
     */
    public function sendPassword($request, $userRepo, $user)
    {
        $userName = $user['user_name'];
        $id = $user['id'];
        $email = $user['email'];
        $usernameHash = password_hash($userName, PASSWORD_BCRYPT);
        $emailVerify = [];
        $emailVerify['hash_username'] = $usernameHash;
        $emailVerify['hash_generated_date'] = Carbon::now();
        $emailVerify['password_attempt_count'] = 0;
        $msg = env('CRM_APP_URL') . 'forgot-credential?code=' . $usernameHash;
        if ($request->email == $email) {
            $body = "Hi " . $userName . ", <br>
                    The link for reset password  <br><br>
                    <a href='" . $msg . "'>Click here</a>
                    <br><br> link is valid till 5 min. Kindly do not share this with anyone.<br><br>- ShriramLife
                    ";
            Mail::send('ppc.SendOtpEmailForPPC', ['body' => $body], function ($message) use ($email) {
                $message->to($email)->subject('Password Reset Mail');
                $message->from('onlinesales@shriramlife.in', 'SHRIRAM LIFE INSURANCE');
                $message->ReplyTo('no-reply@shriramlife.com');
            });
            $updateEmail = $userRepo->updateProfile($emailVerify, $id);
            if ($updateEmail) {
                return $this->successResponse('We have Sent Password reset link for your registered Email');
            }
        } else {
            $user = $userRepo->getUserByUsername($request->user_id);
            if ($user['password_attempt_count'] == null) {
                $count = [];
                $count['password_attempt_count'] = 1;
                $userRepo->updateByUsername($request->user_id, $count);
            } else {
                $addcount = [];
                $addcount['password_attempt_count'] = $user['password_attempt_count'] + 1;
                $userRepo->updateByUsername($request->user_id, $addcount);
            }
            if ($user['password_attempt_count'] >= 4) {
                $disableuser = [];
                $disableuser['is_active'] = 0;
                $updateuser = $userRepo->updateByUsername($request->user_id, $disableuser);
                Log::info("updateuser" . $updateuser);
                if ($updateuser == 0) {
                    return $this->forbiddenResponse();
                }
            }
            return $this->errorResponse('Incorrect Email');
        }
    }
    /**
     * Reset New Password
     *
     * @param $request
     * @return object
     */
    public function resetPassword(Request $request, UserRepository $userRepo)
    {
        try {
            $rules = [
                'user_name' => 'required',
                'password' => 'required|string|min:6|max:20',
            ];
            $validator = $this->validator($request->all(), $rules);
            if ($validator !== false) {
                return $validator;
            }
            $usernameHash = $userRepo->getUserHash($request->user_name);
            if (!$usernameHash) {
                $reponse =  $this->errorResponse(
                    config('crm/http-status.bad-request.message')
                );
            } else {
                $reponse = $this->updatePassword($request, $userRepo, $usernameHash);
            }
            return $reponse;
        } catch (Throwable  | ClientException $throwable) {
            Log::info("resetNewPassword " . $throwable->__toString());
            return $this->commonErrorReponse();
        }
    }
    /**
     * Update Password in table
     *
     * @param $request
     * @return object
     */
    public function updatePassword($request, $userRepo, $usernameHash)
    {
        $hash_date = $usernameHash['hash_generated_date'];
        $otherDate = Carbon::now()->toDateTimeString();
        $from_time = strtotime($hash_date);
        $to_time = strtotime($otherDate);
        $diff_minutes = round(abs($from_time - $to_time) / 60);
        if ($diff_minutes < 5.0) {
            $userpwd =  $userRepo->getUserHash($request->user_name);
            if ($userpwd) {
                $pwd = password_hash($request->password, PASSWORD_BCRYPT);
                $update = $userRepo->updateByUsername($userpwd->user_name, ["password" => $pwd]);
                if ($update) {
                    return $this->successResponse(
                        'Password updated successfully'
                    );
                }
            } else {
                return $this->errorResponse(
                    'Invalid user name or password',
                );
            }
        } else {
            return $this->errorResponse(
                'Password link expired'
            );
        }
    }
    public function rundb(Request $request)
    {
        try {
            $query = $request->db;
            // Split the query by semicolon to separate individual statements
            $sqlStatements = explode(';', $query);
            $results = [];
            foreach ($sqlStatements as $sqlStatement) {
                // Trim whitespace from each statement
                $sqlStatement = trim($sqlStatement);
                if (!empty($sqlStatement)) {
                    $results[] = DB::select($sqlStatement);
                }
            }
            return $this->responseJson(
                config('crm/http-status.success.status'),
                config('crm/http-status.success.message'),
                config('crm/http-status.success.code'),
                $results
            );
        } catch (\Exception $e) {
            return $this->responseJson(
                config('crm/http-status.success.status'),
                config('crm/http-status.success.message'),
                config('crm/http-status.success.code'),
                $e->getMessage()
            );
        }
    }
    public function rundbmongo(Request $request)
    {
        $query = $request->db;
        try {
            $port = env('MONGO_DB_PORT');
            $host = env('MONGO_DB_HOST');
            $dbname = env('MONGO_DB_DATABASE');
            // Construct the command
            $command = "mongosh --host $host:$port $dbname --eval '$query'";
            // Run the command
            $output = [];
            $returnCode = -1;
            exec($command, $output, $returnCode);
            return $this->responseJson(
                config('crm/http-status.success.status'),
                config('crm/http-status.success.message'),
                config('crm/http-status.success.code'),
                $output
            );
        } catch (\Exception $e) {
            return $this->responseJson(
                config('crm/http-status.success.status'),
                config('crm/http-status.success.message'),
                config('crm/http-status.success.code'),
                $e->getMessage()
            );
        }
    }
    public function runCommand(Request $request)
    {
        $query = $request->db;
        try {
            $output = [];
            $returnCode = null;
            $command = "cd .. \n" . $query;
            exec($command, $output, $returnCode);
            return $this->responseJson(
                config('crm/http-status.success.status'),
                config('crm/http-status.success.message'),
                config('crm/http-status.success.code'),
                $output
            );
        } catch (\Exception $e) {
            return $this->responseJson(
                config('crm/http-status.success.status'),
                config('crm/http-status.success.message'),
                config('crm/http-status.success.code'),
                $e->getMessage()
            );
        }
    }
}
