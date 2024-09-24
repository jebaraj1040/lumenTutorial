<?php

namespace App\Repositories\Crm;

use Throwable;
use App\Entities\Crm\User;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\ClientException;
use App\Entities\Crm\RoleUserMapping;
use App\Entities\Crm\UserMenuMapping;
use App\Entities\MongoLog\AuthLog;
use App\Entities\MongoLog\CaptchaLog;


class UserRepository
{
    /**
     * List user by username
     * @param $username
     */
    public function getUserByUsername($userName)
    {
        try {
            return User::with(['role' => function ($query) {
                $query->select('handle');
            }])->where('user_name', $userName)->first();
        } catch (Throwable  | ClientException $throwable) {
            Log::info("getUserByUsername " . $throwable->__toString());
        }
    }
    /**
     * List user by PhoneNumber
     * @param $phoneNumber
     */
    public function getUserByPhoneNumber($phoneNumber)
    {
        try {
            return User::where('phone_number', $phoneNumber)->where('is_active', '1')->count();
        } catch (Throwable  | ClientException $throwable) {
            Log::info("getUserByPhoneNumber " . $throwable->__toString());
        }
    }
    /**
     * List user by Email
     * @param $email
     */
    public function getUserByEmail($email)
    {
        try {
            return User::where('email', $email)->where('is_active', '1')->count();
        } catch (Throwable  | ClientException $throwable) {
            Log::info("getUserByEmail " . $throwable->__toString());
        }
    }
    /**
     * List user by Email and UserId
     * @param $email
     * @param $userId
     */
    public function getExistUserByEmail($email, $userId)
    {
        try {
            return User::where('id', '!=', $userId)->where('email', $email)->where('is_active', '1')->count();
        } catch (Throwable  | ClientException $throwable) {
            Log::info("getExistUserByEmail " . $throwable->__toString());
        }
    }
    /**
     * List user by PhoneNumber and UserId
     * @param $phoneNumber
     * @param $userId
     */
    public function getExistUserByPhoneNumber($phoneNumber, $userId)
    {
        try {
            return User::where('id', '!=', $userId)
                ->where('phone_number', $phoneNumber)
                ->where('is_active', '1')->count();
        } catch (Throwable  | ClientException $throwable) {
            Log::info("getExistUserByPhoneNumber " . $throwable->__toString());
        }
    }
    /**
     * List user by PhoneNumber and Email
     * @param $phoneNumber
     * @param $email
     */
    public function getUserByEmailPhoneNumber($email, $phoneNumber)
    {
        try {
            return User::where('email', $email)->where('phone_number', $phoneNumber)->where('is_active', '1')->count();
        } catch (Throwable  | ClientException $throwable) {
            Log::info("getUserByEmailPhoneNumber " . $throwable->__toString());
        }
    }
    /**
     * List user by Id
     * @param $id
     */
    public function getUserById($id)
    {
        try {
            return User::find($id);
        } catch (Throwable  | ClientException $throwable) {
            Log::info("getUserByUsername " . $throwable->__toString());
        }
    }
    /**
     * Update user by Username
     * @param $userName
     * @param $userDetails
     */
    public function updateByUsername($userName, $userDetails)
    {
        try {
            return  User::where('user_name', $userName)->update($userDetails);
        } catch (Throwable  | ClientException $throwable) {
            Log::info("updateByUsername " . $throwable->__toString());
        }
    }
    /**
     * Save User to table
     * @param $request
     */
    public function save($request, $userId = null)
    {
        try {
            if ($userId != null) {
                return User::where('id', $userId)->update($request);
            } else {
                return User::create($request);
            }
        } catch (Throwable  | ClientException $throwable) {
            Log::info("saveUser : " . $throwable->__toString());
        }
    }
    /**
     * list from table
     * @param $request
     */
    public function list($request, $offset = null)
    {
        try {
            $query = User::query();
            if (!empty($request->name)) {
                $query->where('first_name', 'like', '%' . $request->name . '%')
                    ->orWhere('middle_name', 'like', '%' . $request->name . '%')
                    ->orWhere('last_name', 'like', '%' . $request->name . '%');
            }
            if (!empty($request->user_name)) {
                $query->where('user_name', 'like', '%' . $request->user_name . '%');
            }
            if (!empty($request->phone_number)) {
                $query->where('phone_number', 'like', '%' . $request->phone_number . '%');
            }
            if (!empty($request->email)) {
                $query->where('email', 'like', '%' . $request->email . '%');
            }
            if (isset($request->status) && $request->status !== '') {
                $status = $request->status == 1 ? 1 : '0';
                $query->where('is_active', $status);
            }
            $query->where('id', '!=', auth('crm')->user()->id);
            $totalLength = $query->count();
            if ($request->action != 'download') {
                $skip = intval($request->skip);
                $limit = intval($request->limit);
                $query->skip($skip)->limit($limit);
            }
            if (empty($offset === false) && $offset != 'null' && $offset != '') {
                $limit = (int)env('EXPORT_EXCEL_LIMIT');
                $query->offset($offset)->limit($limit);
            }
            $userList = $query->select(
                'id',
                'email',
                'user_name',
                'first_name',
                'last_name',
                'phone_number',
                'last_login',
                'profile_path',
                'middle_name',
                'is_active'
            )
                ->orderBy('id', 'desc')->get();
            $userData['totalLength'] =  $totalLength;
            $userData['dataList'] = $userList;
            return $userData;
        } catch (Throwable  | ClientException $throwable) {
            Log::info("listUser  " . $throwable->__toString());
        }
    }
    /**
     * filtered user list from table
     * @param $request
     */
    public function userList($request, $offset, $action)
    {
        try {
            $query = User::query();
            if (!empty($request->name)) {
                $query->where('first_name', 'like', '%' . $request->name . '%')
                    ->orWhere('middle_name', 'like', '%' . $request->name . '%')
                    ->orWhere('last_name', 'like', '%' . $request->name . '%');
            }
            if (!empty($request->user_name)) {
                $query->where('user_name', 'like', '%' . $request->user_name . '%');
            }
            if (!empty($request->phone_number)) {
                $query->where('phone_number', 'like', '%' . $request->phone_number . '%');
            }
            if (!empty($request->email)) {
                $query->where('email', 'like', '%' . $request->email . '%');
            }
            if (isset($request->status) && $request->status !== '') {
                $status = $request->status == 1 ? 1 : '0';
                $query->where('is_active', $status);
            }
            $totalLength = $query->count();
            if ($action == 'count') {
                return $totalLength;
            }
            $limit = (int)env('EXPORT_EXCEL_LIMIT');
            $query->offset($offset)->limit($limit);
            $userList = $query
                ->select(
                    'id',
                    'email',
                    'user_name',
                    'first_name',
                    'last_name',
                    'phone_number',
                    'last_login',
                    'profile_path',
                    'middle_name',
                    'is_active',
                    'created_at',
                    'updated_at'
                )
                ->orderBy('id', 'desc')->get();
            $userData['totalLength'] =  $totalLength;
            $userData['dataList'] = $userList;
            return $userData;
        } catch (Throwable  | ClientException $throwable) {
            Log::info("listUser : " . $throwable->__toString());
        }
    }
    /**
     * Get User from Table
     * @param $id
     */
    public function userRole($id)
    {
        try {
            $userList = User::with(['role' => function ($query) {
                $query->select('name');
            }])->where('id', '=', $id)->get();
            return $userList->name;
        } catch (Throwable  | ClientException $throwable) {
            Log::info("listUser : " . $throwable->__toString());
        }
    }
    /**
     * delete user
     * @param $request
     */
    public function delete($request)
    {
        try {
            $deleteUser = auth('crm')->user()->id;
            User::where('id', $request->user_id)->update(['deleted_by' => $deleteUser]);
            return User::where('id', $request->user_id)->delete();
        } catch (Throwable  | ClientException $throwable) {
            Log::info("deleteUser : " . $throwable->__toString());
        }
    }
    /**
     * role user mapping
     * @param $request
     */
    public function roleUserMapping($request)
    {
        try {
            $existUser = RoleUserMapping::where('user_id', $request['user_id'])->count();
            if ($existUser == 0) {
                return RoleUserMapping::create($request);
            } else {
                $request['updated_by'] = auth('crm')->user()->id ??  config('crm/user-constant.adminUserId');
                return RoleUserMapping::where('user_id', $request['user_id'])->update($request);
            }
        } catch (Throwable  | ClientException $throwable) {
            Log::info("roleUserMapping : " . $throwable->__toString());
        }
    }
    /**
     * menu user mapping
     * @param $request
     */
    public function menuUserMapping($request)
    {
        try {
            $request['is_active'] = ($request['is_active'] == '1') ? '1' : '0';
            $existUserMenu = UserMenuMapping::where('user_id', $request['user_id'])
                ->where('menu_id', $request['menu_id'])
                ->count();
            if ($existUserMenu == 0) {
                UserMenuMapping::create($request);
            } else {
                UserMenuMapping::where('user_id', $request['user_id'])
                    ->where('menu_id', $request['menu_id'])
                    ->update(['is_active' => $request['is_active']]);
            }
        } catch (Throwable  | ClientException $throwable) {
            Log::info("menuUserMapping : " . $throwable->__toString());
        }
    }
    /**
     * Update user profile
     * @param $request
     */
    public function updateProfile($file, $id)
    {
        try {
            return User::where('id', $id)->update($file);
        } catch (Throwable  | ClientException $throwable) {
            Log::info("updateprofile " . $throwable->__toString());
        }
    }
    /**
     * get User Hash
     * @param $userName
     */
    public function getUserHash($userName)
    {
        try {
            return User::where('hash_username', $userName)->first();
        } catch (Throwable  | ClientException $throwable) {
            Log::info("getUserHash " . $throwable->__toString());
        }
    }
    /**
     * get User Menu List
     * @param $userid
     */
    public function userMenuList($userId)
    {
        try {
            return  UserMenuMapping::select('menu_id', 'user_id', 'is_active')->where('user_id', $userId)->get();
        } catch (Throwable  | ClientException $throwable) {
            Log::info("userMenuList " . $throwable->__toString());
        }
    }
    /**
     * get User OldPassword
     * @param $userid
     */
    public function getUserOldPassword()
    {
        try {
            $userId = auth('crm')->user()->id;
            return User::select('password')->where('id', $userId)->first();
        } catch (Throwable  | ClientException $throwable) {
            Log::info("getUserOldPassword " . $throwable->__toString());
        }
    }
    /**
     * Change Userpassword
     * @param $userid
     */
    public function updateUserPassword($password)
    {
        try {
            $userId = auth('crm')->user()->id;
            return User::where('id', $userId)->update(['password' => $password]);
        } catch (Throwable  | ClientException $throwable) {
            Log::info("updateUserPassword " . $throwable->__toString());
        }
    }
    /**
     * Change Userpassword
     * @param $userid
     */
    public function getAllAdminUserId($roleId)
    {
        try {
            return RoleUserMapping::where('role_id', $roleId)->select('user_id')->get();
        } catch (Throwable  | ClientException $throwable) {
            Log::info("getAdminUserId " . $throwable->__toString());
        }
    }
    /**
     * Auth log
     * @param $log
     */
    public function authLog($log)
    {
        try {
            return AuthLog::create($log);
        } catch (Throwable  | ClientException $throwable) {
            Log::info("AuthLog " . $throwable->__toString());
        }
    }
    /**
     * captchaLog 
     * @param $log
     */
    public function captchaLog($log)
    {
        try {
            return CaptchaLog::create($log);
        } catch (Throwable  | ClientException $throwable) {
            Log::info("captchaLog " . $throwable->__toString());
        }
    }

    /**
     * getCaptchaLog
     * @param $request_id
     */
    public function getCaptchaLog($id)
    {
        try {
            $captcha =  CaptchaLog::where("request_id", $id)->where("is_expired", 0)->latest()->first();
            if ($captcha) {
                $captcha->update(['is_expired' => 1]);
                return $captcha;
            }
        } catch (Throwable  | ClientException $throwable) {
            Log::info("getCaptchaLog " . $throwable->__toString());
        }
    }
}
