<?php

namespace App\Services\Crm;

use App\Entities\Crm\MenuMaster;
use App\Services\Service;
use GuzzleHttp\Exception\ClientException;
use Throwable;
use Illuminate\Http\Request;
use App\Repositories\Crm\UserRepository;
use App\Repositories\Crm\RoleUserRepository;
use App\Repositories\Crm\RoleRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Utils\CrmTrait;

class UserService extends Service
{
    use CrmTrait;
    public const INVALID_USER_MESSAGE = "Invalid user name";
    private $userRepo;
    /**
     * Create a new Service instance.
     *
     * @param
     * @return void
     */
    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
    }
    /**
     * Create and Update  User.
     *
     * @param
     * @return void
     */
    public function saveUser(Request $request, UserRepository $userRepo)
    {
        try {
            $userId = $request->user_id ?? null;
            $validator = $this->validateUserData($request, $userRepo, $userId);
            if ($validator !== false) {
                return $validator;
            }
            $userInsert = $this->processUserDetails($request, $userRepo, $userId, 'save');
            if ($userInsert) {
                return $this->successResponse(
                    config('crm/http-status.add.message'),
                );
            } else {
                return $this->errorResponse(
                    config('crm/http-status.error.message')
                );
            }
        } catch (Throwable | ClientException $throwable) {
            throw new Throwable(
                Log::info("Service : UserService , Method : saveUser : %s" . $throwable->__toString())
            );
        }
    }
    /**
     * validateUserData.
     *
     * @param $request, $userRepo, $userId
     * @return void
     */
    private function validateUserData(Request $request, UserRepository $userRepo, $userId)
    {
        $rules = [
            'email' => 'required|email',
            'user_name' => ['required'],
            'first_name' => 'required',
            'phone_number' => 'required|digits:10'
        ];
        $validator = $this->validator($request->all(), $rules);
        if ($validator !== false) {
            return $validator;
        }
        $response = false;
        if (!$userId) {
            $userName = $userRepo->getUserByUsername($request->user_name);
            if ($userName) {
                return $this->errorResponse('User already registered with username'
                    . '-' . $request->user_name . ',Please sign up using different username');
            }
        }
        $userPhoneNumber = $userId ? $userRepo->getExistUserByPhoneNumber($request->phone_number, $userId)
            : $userRepo->getUserByPhoneNumber($request->phone_number);
        if ($userPhoneNumber && $userPhoneNumber > 0) {
            return $this->errorResponse('This Phone Number is already taken (' . $request->phone_number . ')
            . Please use different Phone Number.');
        }
        $userEmail = $userId ? $userRepo->getExistUserByEmail($request->email, $userId)
            : $userRepo->getUserByEmail($request->email);
        if ($userEmail && $userEmail > 0) {
            return $this->errorResponse('This Email is already taken (' . $request->email . ')
            . Please use different Email.');
        }
        return $response;
    }
    /**
     * processUserDetails.
     *
     * @param $request, $userRepo, $userId, $action
     * @return void
     */
    private function processUserDetails(Request $request, UserRepository $userRepo, $userId, $action)
    {
        $userName = $userRepo->getUserByUsername($request->user_name);
        if (isset($request->file) && $request->file !== null) {
            $path = '/' . 'crm/user-profile' . '/';
            $contentType =  ["jpg", "jpeg", "png"];
            $userDetails['profile_path'] = $this->processFile($request, $userName, $path, $contentType);
        }
        if ($action == 'save') {
            $userDetails['password'] =  password_hash($request->password, PASSWORD_BCRYPT);
        }
        if ($action == 'update') {
            if (isset($request->password) && $request->password !== null) {
                $userDetails['password'] = password_hash($request->password, PASSWORD_BCRYPT);
            } else {
                $userDetails['password'] = $userName['password'];
            }
        }
        $isActive = $request->is_active ?? "1";
        $userDetails['email'] = $request->email;
        $userDetails['user_name'] = $request->user_name;
        $userDetails['first_name'] = $request->first_name;
        $userDetails['middle_name'] = $request->middle_name;
        $userDetails['last_name'] = $request->last_name;
        $userDetails['phone_number'] = $request->phone_number;
        $userDetails['is_active'] = $isActive;
        $userDetails['created_by'] = auth('crm')->user()->id ?? config('crm/user-constant.adminUserId');
        if ($userId) {
            $userDetails['updated_by'] = auth('crm')->user()->id;
        }
        $userInsert = $this->userRepo->save($userDetails, $userId);
        if ($request->role_id && $userInsert) {
            $userRoleMapping = [
                'role_id' => $request->role_id,
                'user_id' => $userId ?: $userInsert->id,
                'created_by' => auth('crm')->user()->id
            ];
            $this->userRepo->roleUserMapping($userRoleMapping);
        }
        if ($request['menu_ids']) {
            $menuIdsArray = json_decode($request['menu_ids'], true);
            $menuIds = array_filter($menuIdsArray, function ($item) {
                return $item['status'] == 1;
            });
            $parentList = [];
            $parentLists = [];
            $parentIds = [];
            foreach ($menuIds as $menuId) {
                $parent = MenuMaster::select('parent_id')
                    ->where('is_parent', 0)
                    ->where('id', $menuId)
                    ->first();
                // Check if a parent was found
                if ($parent) {
                    $parentList[] = [
                        'menu_id' => $parent->parent_id,
                        'status' => 1
                    ];
                    $parentIds[] = $parent->parent_id;
                }
            }
            $parents = MenuMaster::where('is_parent', 1)->pluck('id')->toArray();
            $distinctNumbers = array_unique($parentIds);
            $diffNumbers = array_diff($parents, $distinctNumbers);
            foreach ($diffNumbers as $diffNumber) {
                $parentLists[] = [
                    'menu_id' => $diffNumber,
                    'status' => 0
                ];
            }
            $combinedArray = array_merge($menuIdsArray, $parentList, $parentLists);
            foreach ($combinedArray as $menu_Ids) {
                $userMenuMapping = [
                    'user_id' => $userId ?: $userInsert->id,
                    'menu_id' => $menu_Ids['menu_id'],
                    'is_active' => (string)$menu_Ids['status'],
                    'created_by' => auth('crm')->user()->id
                ];
                $this->userRepo->menuUserMapping($userMenuMapping);
            }
        }
        return $userInsert
            ? $this->successResponse(config('crm/http-status.add.message'))
            : $this->errorResponse(config('crm/http-status.error.message'));
    }
    /**
     * Update  User.
     *
     * @param $request,  $userRepo
     * @return void
     */
    public function updateUser(Request $request, UserRepository $userRepo)
    {
        try {
            $userData = $userRepo->getUserByUsername($request->user_name);
            if (!$userData) {
                return $this->errorResponse('Invalid user name');
            }
            $userId = $userData->id;
            $validator = $this->validateUserData($request, $userRepo, $userId);
            if ($validator !== false) {
                return $validator;
            }
            $userInsert = $this->processUserDetails($request, $userRepo, $userId,'update');
            if ($userInsert) {
                $userSelectiveData = $this->getUserSelectiveData($userRepo, $request->user_name);
                $userSelectiveData['msg'] = config('crm/http-status.update.message');
                $response = $this->successResponse($userSelectiveData);
            } else {
                $response = $this->commonErrorReponse();
            }
            return $response;
        } catch (Throwable | ClientException $throwable) {
            throw new Throwable(Log::info("Service: UserService, Method: UpdateUser: %s" . $throwable->__toString()));
        }
    }
    /**
     * getUserSelectiveData.
     *
     * @param $username,  $userRepo
     * @return void
     */
    private function getUserSelectiveData($userRepo, $username)
    {
        $userData = $userRepo->getUserByUsername($username);
        return [
            'profile_path' => $userData->profile_path ?? null,
            'user_name' => $userData->first_name . ' ' . $userData->middle_name . ' ' . $userData->last_name
        ];
    }
    /**
     * Update user profile image.
     *
     * @param $request, $userRepo
     * @return void
     */
    public function updateProfileImage(Request $request, UserRepository $userRepo)
    {
        try {
            $userName = auth('crm')->user()->user_name;
            if (isset($request->user_name)) {
                $userName = $request->user_name;
            }
            $userData = $userRepo->getUserByUsername($userName);
            if (!$userData) {
                return $this->errorResponse(self::INVALID_USER_MESSAGE);
            }
            if (isset($request->file) && $request->file !== null) {
                $path = '/' . 'crm/user-profile' . '/';
                $contentType =  ["jpg", "jpeg", "png"];
                $userDetails['profile_path'] = $this->processFile($request, $userName, $path, $contentType);
                $updateProfile = $userRepo->updateProfile($userDetails, $userData->id);
                if ($updateProfile) {
                    $userDetails['msg'] = config('crm/http-status.update.message');
                    return $this->successResponse($userDetails);
                }
            }
        } catch (Throwable | ClientException $throwable) {
            Log::info("updateProfileImage " . $throwable->__toString());
            return $this->commonErrorReponse();
        }
    }
    /**
     * getUser.
     *
     * @param $request
     * @return void
     */
    public function getUser(Request $request)
    {
        try {
            $userList = $this->userRepo->list($request);
            $userList['msg'] = "User List Retrived Successfully...";
            return $this->successResponse($userList);
        } catch (Throwable | ClientException $throwable) {
            throw new Throwable(Log::info("Service  UserService , Method : getUser : %s" . $throwable->__toString()));
        }
    }
    /**
     * getUserData.
     *
     * @param $request, $userRepo, $roleRepo, $userRoleRepo
     * @return void
     */
    public function getUserData(
        Request $request,
        UserRepository $userRepo,
        RoleUserRepository $userRoleRepo,
        RoleRepository $roleRepo
    ) {
        try {
            $userData = $userRepo->getUserById($request->user_id);
            if (!$userData) {
                return $this->errorResponse(self::INVALID_USER_MESSAGE);
            }
            $userId = $userData->id;
            $roleId = $userRoleRepo->getUserRoleById($userId);
            $userData['role'] = '';
            if ($roleId) {
                $userData['role']  = $roleRepo->getroleById($roleId->role_id);
            }
            $userData['menu'] = $userRepo->userMenuList($userId);
            return $this->successResponse($userData);
        } catch (Throwable  | ClientException $throwable) {
            throw new Throwable(Log::info("Service : UserService , Method : getUser : %s"
                . $throwable->__toString()));
        }
    }
    /**
     * getData.
     *
     * @param  $userRepo, $roleRepo, $userRoleRepo
     * @return void
     */
    public function getData(
        UserRepository $userRepo,
        RoleUserRepository $userRoleRepo,
        RoleRepository $roleRepo
    ) {
        try {
            $userData = $userRepo->getUserById(auth('crm')->user()->id);
            if (!$userData) {
                return $this->errorResponse('Invalid user name');
            }
            $userId = $userData->id;
            $roleId = $userRoleRepo->getUserRoleById($userId);
            $userData['role'] = '';
            if ($roleId) {
                $userData['role']  = $roleRepo->getroleById($roleId->role_id);
            }
            $userData['menu'] = $userRepo->userMenuList(auth('crm')->user()->id);
            return $this->successResponse($userData);
        } catch (Throwable  | ClientException $throwable) {
            throw new Throwable(Log::info("Service : UserService , Method : getUser : %s" . $throwable->__toString()));
        }
    }
    /**
     * deleteUser.
     *
     * @param
     * @return void
     */
    public function deleteUser(Request $request)
    {
        try {
            $deleteUser = $this->userRepo->delete($request);
            if ($deleteUser) {
                return $this->successResponse(config('crm/http-status.delete.message'));
            } else {
                return $this->commonErrorReponse();
            }
        } catch (Throwable  | ClientException $throwable) {
            throw new Throwable(Log::info("Service : UserService , Method : deleteUser : %s"
                . $throwable->__toString()));
        }
    }
    /**
     * Update Password
     *
     * @param $request, $userRepo
     * @return void
     */
    public function updatePassword(Request $request, UserRepository $userRepo)
    {
        try {
            $validator = $this->validateUpdatePasswordRequest($request);
            if ($validator !== false) {
                return $validator;
            }
            $reponse = null;
            $userPassword = $userRepo->getUserByUsername($request->user_name);
            if (!$userPassword) {
                $reponse = $this->errorResponse('Provided username is not valid');
            } else {
                $reponse = $this->changePassword($userRepo, $request, $userPassword);
            }
            return $reponse;
        } catch (Throwable | ClientException $throwable) {
            Log::info("updatePassword " . $throwable->__toString());
            return $this->commonErrorReponse();
        }
    }
    /**
     * validateUpdatePasswordRequest
     *
     * @param $request, $userRepo
     * @return void
     */
    private function validateUpdatePasswordRequest(Request $request)
    {
        $rules = [
            'user_name' => 'required',
            'password' => 'required|string|min:6|max:20',
            'current_password' => 'required'
        ];
        return $this->validator($request->all(), $rules);
    }
    /**
     * changePassword
     *
     * @param $request, $userRepo, $userPassword
     * @return void
     */
    private function changePassword(
        UserRepository $userRepo,
        Request $request,
        $userPassword
    ) {
        $currentPasswordHash = $userPassword['password'];
        if (Hash::check($request->current_password, $currentPasswordHash)) {
            $newPassword = Hash::make($request->password);
            $update = $userRepo->updateByUsername($userPassword->user_name, ["password" => $newPassword]);
            if ($update) {
                return $this->successResponse('Password has been changed');
            }
            return $this->errorResponse('Something went wrong.');
        }
        return $this->errorResponse('Incorrect Password');
    }
    /**
     * resetPassword
     *
     * @param $request
     * @return void
     */
    public function resetPassword(Request $request)
    {
        try {
            $userPassword = $this->userRepo->getUserOldPassword();
            if ($userPassword) {
                $currentpassword = $userPassword->password;
                $response = null;
                if (Hash::check($request->oldPassword, $currentpassword)) {
                    $newPassword = password_hash($request->confirmPassword, PASSWORD_BCRYPT);
                    $update = $this->userRepo->updateUserPassword($newPassword);
                    if ($update) {
                        $response = $this->successResponse('Password Changed Successfully');
                    } else {
                        $response = $this->errorResponse('Somthing went wrong.');
                    }
                } else {
                    $response =  $this->errorResponse('Incorrect Old Password');
                }
                return $response;
            } else {
                return $this->errorResponse('Provided password is not valid');
            }
        } catch (Throwable  | ClientException $throwable) {
            throw new Throwable(Log::info("Service : UserService , Method : changePassword : %s"
                . $throwable->__toString()));
        }
    }
    /**
     * Export Log.
     *
     * @param $request
     *
     */
    public function exportLog(Request $request)
    {
        try {
            $repository = new UserRepository();
            $data['methodName'] = 'list';
            $data['fileName'] = 'User-Report-';
            $data['moduleName'] = 'User';
            return $this->exportData($request, $repository, $data);
        } catch (Throwable  | ClientException $throwable) {
            Log::info("UserExport " . $throwable->__toString());
            return $this->commonErrorReponse();
        }
    }
}
