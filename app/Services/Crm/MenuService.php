<?php

namespace App\Services\Crm;

use App\Services\Service;
use Illuminate\Http\Request;
use Throwable;
use GuzzleHttp\Exception\ClientException;
use App\Repositories\Crm\MenuRepository;
use App\Repositories\Crm\RoleRepository;
use App\Repositories\Crm\UserRepository;
use Illuminate\Support\Facades\Log;
use App\Utils\CrmTrait;
use Illuminate\Http\JsonResponse;

class MenuService extends Service
{
    use CrmTrait;
    private $menuRepo;
    private $roleRepo;
    private $userRepo;
    /**
     * Create a new Service instance.
     *
     * @param
     * @return void
     */
    public function __construct(MenuRepository $menuRepo, RoleRepository $roleRepo, UserRepository $userRepo)
    {
        $this->menuRepo = $menuRepo;
        $this->roleRepo = $roleRepo;
        $this->userRepo = $userRepo;
    }
    /**
     * Create and Update  Menu.
     *
     * @param
     * @return void
     */
    public function saveMenu(Request $request)
    {
        try {
            $requestName = $request->name;
            $menuId = $request->menu_id ?? null;
            $validator = $this->validateMenuRequest($request);
            if ($validator !== false) {
                return $validator;
            }
            $menuDetails = $this->prepareMenuDetails($request);
            $saveMenu = $this->menuRepo->save($menuDetails, $menuId);
            if (strcasecmp($requestName, $saveMenu) == 0) {
                return $this->responseJson(
                    config('crm/http-status.error.status'),
                    config('crm/http-status.error.message'),
                    config('crm/http-status.error.code'),
                    []
                );
            }
            if (!$menuId) {
                $this->assignMenuToAdminRoles($saveMenu);
                $this->assignMenuToAdminUsers($saveMenu);
            }
            return $this->getResponse($saveMenu);
        } catch (Throwable | ClientException $throwable) {
            Log::info("Service: MenuService, Method: saveMenu: %s" . $throwable->__toString());
        }
    }
    /**
     * Create and Update  Menu.
     *
     * @param
     * @return void
     */
    public function parentList(Request $request)
    {
        try {
            $mainMenu = $this->menuRepo->parentList($request);
            $parentList['parentList'] = $mainMenu;
            $parentList['msg'] = config('crm/http-status.success.message');
            return  $this->successResponse($parentList);
        } catch (Throwable  | ClientException $throwable) {
            throw new Throwable(Log::info("Service : MenuService , Method : parentList : %s" . $throwable->__toString()));
        }
    }
    /**
     * validateMenuRequest.
     *
     * @param $request
     * @return void
     */
    private function validateMenuRequest($request)
    {
        $rules = [
            'name' => 'required',
            // 'slug' => 'required',
            'is_active' => 'required'
        ];
        return $this->validator($request->all(), $rules);
    }
    /**
     * prepareMenuDetails.
     *
     * @param $request
     * @return void
     */
    private function prepareMenuDetails($request)
    {
        $menuDetails = [
            'name' => $request->name ?? null,
            'handle' => $request->name ? strtolower(str_replace(" ", "-", $request->name)) : null,
            'slug' => $request->slug ?? null,
            'is_parent' => $request->is_parent ?? null,
            'parent_id' => $request->parent_id ?? null,
            'is_active' => $request->is_active ?? '1',
            'created_by' => auth('crm')->user()->id ?? config('crm/user-constant.adminUserId')
        ];
        if ($request->menu_id) {
            $menuDetails['updated_by'] = auth('crm')->user()->id ?? config('crm/user-constant.adminUserId');
        }
        return $menuDetails;
    }
    /**
     * assignMenuToAdminRoles.
     *
     * @param $saveMenu
     * @return object
     */
    private function assignMenuToAdminRoles($saveMenu)
    {
        $roleId = $this->roleRepo->getAdminRoleId();
        if ($roleId) {
            $roleMenuMapping = [
                'role_id' => $roleId ?? null,
                'menu_id' => $saveMenu->id,
                'is_active' => '1',
                'created_by' => auth('crm')->user()->id ?? config('crm/user-constant.adminUserId')
            ];
            $this->roleRepo->saveMenu($roleMenuMapping);
        } else {
            Log::info('Admin role not exist');
        }
    }
    /**
     * assignMenuToAdminUsers.
     *
     * @param $saveMenu
     * @return object
     */
    private function assignMenuToAdminUsers($saveMenu)
    {
        $roleId = $this->roleRepo->getAdminRoleId();
        $userId = $this->userRepo->getAllAdminUserId($roleId);
        if ($userId) {
            foreach ($userId as $uid) {
                $userMenuMapping = [
                    'user_id' => $uid->user_id,
                    'menu_id' => $saveMenu->id,
                    'is_active' => 1,
                    'created_by' => auth('crm')->user()->id ?? config('crm/user-constant.adminUserId')
                ];
                $this->userRepo->menuUserMapping($userMenuMapping);
            }
        } else {
            Log::info('Admin user not exist');
        }
    }
    /**
     * getResponse.
     *
     * @param $saveMenu
     * @return JsonResponse
     */
    private function getResponse($saveMenu): JsonResponse
    {
        if ($saveMenu) {
            return $this->responseJson(
                config('crm/http-status.add.status'),
                config('crm/http-status.add.message'),
                config('crm/http-status.add.code'),
                []
            );
        }
        return $this->responseJson(
            config('crm/http-status.error.status'),
            config('crm/http-status.error.message'),
            config('crm/http-status.error.code'),
            []
        );
    }
    /**
     * list  Menu.
     *
     * @param
     * @return void
     */
    public function getMenu(Request $request)
    {
        try {
            $mainMenu = $this->menuRepo->list($request);
            $sideBarMenu = $this->roleRepo->sideBarMenulist();
            $roleMenu = $this->menuRepo->roleMenulist();
            $userMenu = $this->menuRepo->userMenuList($request);
            $menuList['mainMenu'] = $mainMenu;
            $menuList['sideBarMenu'] = $sideBarMenu;
            $menuList['roleMenu'] =  $roleMenu;
            $menuList['userMenu'] = $userMenu;
            $menuList['msg'] = config('crm/http-status.success.message');
            return  $this->successResponse($menuList);
        } catch (Throwable  | ClientException $throwable) {
            throw new Throwable(Log::info("Service : MenuService , Method : getMenu : %s" . $throwable->__toString()));
        }
    }
    /**
     * Delete  Menu.
     *
     * @param
     * @return void
     */
    public function deleteMenu(Request $request)
    {
        try {
            $deleteMenu = $this->menuRepo->delete($request);
            if ($deleteMenu) {
                return $this->responseJson(
                    config('crm/http-status.delete.status'),
                    config('crm/http-status.delete.message'),
                    config('crm/http-status.delete.code')
                );
            } else {
                return $this->responseJson(
                    config('crm/http-status.error.status'),
                    config('crm/http-status.error.message'),
                    config('crm/http-status.error.code'),
                    []
                );
            }
        } catch (Throwable  | ClientException $throwable) {
            throw new Throwable(Log::info("Service : MenuService , Method : deleteMenu : %s"
                . $throwable->__toString()));
        }
    }
    /**
     * Get Particular Menu.
     *
     * @param
     * @return void
     */
    public function editMenu(Request $request)
    {
        try {
            $menuId = $request->menu_id ?? null;
            $menuData = $this->menuRepo->edit($menuId);
            $menuData['msg'] = config('crm/http-status.success.message');
            return  $this->successResponse($menuData);
        } catch (Throwable  | ClientException $throwable) {
            throw new Throwable(Log::info("Service : MenuService , Method : editMenu : %s" . $throwable->__toString()));
        }
    }
    /**
     * check access for a user.
     *
     * @param
     * @return void
     */
    public function checkMenuAccess(Request $request)
    {
        try {
            $accessStatus =  $this->roleRepo->sideBarMenulist();
            $menuStatus = true;
            if ($accessStatus) {
                $allowMenu = array();
                foreach ($accessStatus as $menuList) {
                    $data = json_decode($menuList);
                    if ($data->role_menu != null) {
                        $userMenuPermission  = $this->menuRepo->userBasedMenu($data->role_menu->menu_id);
                        if ($userMenuPermission && $userMenuPermission->is_active == 1) {
                            $allowMenu[] = $menuList->slug ?? null;
                        }
                    }
                }
                if (in_array($request->currentUrl, $allowMenu)) {
                    $menuStatus = true;
                } else {
                    $menuStatus = false;
                }
            }
            return $this->responseJson(
                config('crm/http-status.success.status'),
                config('crm/http-status.success.message'),
                config('crm/http-status.success.code'),
                $menuStatus
            );
        } catch (Throwable  | ClientException $throwable) {
            throw new Throwable(Log::info("Service : MenuService , Method : checkMenuAccess : %s"
                . $throwable->__toString()));
        }
    }
    /**
     * epxort menu.
     *
     * @param
     * @return void
     */
    public function export(Request $request)
    {
        try {
            $repository = new MenuRepository();
            $data['methodName'] = 'list';
            $data['fileName'] = 'Export-Menu-Report-';
            $data['moduleName'] = 'Menu';
            return $this->exportData($request, $repository, $data);
        } catch (Throwable   | ClientException $throwable) {
            Log::info("Service : MenuService , Method : export : %s" . $throwable->__toString());
        }
    }
    public function orderstatus(Request $request)
    {
        try {
            $menuData = $this->menuRepo->orderstatus($request);
            return  $this->successResponse($menuData);
        } catch (Throwable   | ClientException $throwable) {
            Log::info("Service : MenuService , Method : orderstatus : %s" . $throwable->__toString());
        }
    }
    public function orderupdate(Request $request)
    {
        try {
            $menuData = $this->menuRepo->orderupdate($request);
            $menuData['msg'] = "data updated";
            return  $this->successResponse($menuData);
        } catch (Throwable   | ClientException $throwable) {
            Log::info("Service : MenuService , Method : orderupdate : %s" . $throwable->__toString());
        }
    }
}
