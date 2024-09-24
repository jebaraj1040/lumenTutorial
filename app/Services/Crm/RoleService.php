<?php

namespace App\Services\Crm;

use App\Entities\Crm\MenuMaster;
use App\Services\Service;
use Illuminate\Http\Request;
use GuzzleHttp\Exception\ClientException;
use Throwable;
use App\Repositories\Crm\RoleRepository;
use Illuminate\Support\Facades\Log;
use App\Utils\CrmTrait;

class RoleService extends Service
{
    use CrmTrait;
    private $roleRepo;
    /**
     * Create a new Service instance.
     *
     * @param
     * @return void
     */
    public function __construct(RoleRepository $roleRepo)
    {
        $this->roleRepo = $roleRepo;
    }
    /**
     * Create and Update  Role.
     *
     * @param
     * @return void
     */
    public function saveRole(Request $request)
    {
        try {
            $roleId = $request->role_id ?? null;
            $rules = [
                'name'    => 'required',
            ];
            $validator = $this->validator($request->all(), $rules);
            if ($validator !== false) {
                return $validator;
            }
            $roleDetails['name'] = $request->name ?? null;
            $roleDetails['handle'] = $request->name ? strtolower(str_replace(" ", "-", $request->name)) : null;
            $roleDetails['created_by'] = auth('crm')->user()->id ?? config('crm/user-constant.adminUserId');
            if ($roleId) {
                $roleDetails['updated_by'] = auth('crm')->user()->id ?? config('crm/user-constant.adminUserId');
            }
            $createRole = $this->roleRepo->save($roleDetails, $roleId);
            if (is_array($createRole) && $createRole['status'] === 422) {
                return $this->errorResponse($createRole['msg']);
            }
            $insertedRoleId = $roleId ? $roleId : $createRole->id;
            if ($createRole) {
                return $this->responseJson(
                    config('crm/http-status.add.status'),
                    config('crm/http-status.add.message'),
                    config('crm/http-status.add.code'),
                    $insertedRoleId
                );
            } else {
                return $this->responseJson(
                    config('crm/http-status.error.status'),
                    config('crm/http-status.error.message'),
                    config('crm/http-status.error.code'),
                    []
                );
            }
        } catch (Throwable | ClientException $throwable) {
            throw new Throwable(Log::info("Service : RoleService , Method : createRole : %s"
                . $throwable->__toString()));
        }
    }
    /**
     * list Roles.
     *
     * @param
     * @return void
     */
    public function getRole(Request $request)
    {
        try {
            $roleList = $this->roleRepo->list($request);
            return $this->responseJson(
                config('crm/http-status.success.status'),
                config('crm/http-status.success.message'),
                config('crm/http-status.success.code'),
                $roleList
            );
        } catch (Throwable | ClientException $throwable) {
            throw new Throwable(Log::info("Service : RoleService , Method : getRole : %s" . $throwable->__toString()));
        }
    }
    /**
     * edit Role.
     *
     * @param
     * @return void
     */
    public function editRole(Request $request)
    {
        try {
            $roleEdit = $this->roleRepo->edit($request);
            $roleMenuEdit = $this->roleRepo->roleMenuList($request);
            $roleData['role'] = $roleEdit;
            $roleData['menu'] = $roleMenuEdit;
            return $this->responseJson(
                config('crm/http-status.success.status'),
                config('crm/http-status.success.message'),
                config('crm/http-status.success.code'),
                $roleData
            );
        } catch (Throwable | ClientException $throwable) {
            throw new Throwable(Log::info("Service : RoleService , Method : editRole : %s"
                . $throwable->__toString()));
        }
    }
    /**
     * delete Roles.
     *
     * @param
     * @return void
     */
    public function deleteRole(Request $request)
    {
        try {
            $deleteRole = $this->roleRepo->delete($request);
            if ($deleteRole != "RoleNotDeleted") {
                return $this->responseJson(
                    config('crm/http-status.delete.status'),
                    config('crm/http-status.delete.message'),
                    config('crm/http-status.delete.code')
                );
            } else {
                return $this->errorResponse('User Assigned To This Role');
            }
        } catch (Throwable | ClientException $throwable) {
            throw new Throwable(Log::info("Service : RoleService , Method : deleteRole : %s"
                . $throwable->__toString()));
        }
    }
    /**
     * save particular role menus.
     *
     * @param
     * @return void
     */
    public function menuMapping(Request $request)
    {
        try {
            $menuIds = array_map(function ($item) {
                return $item['menu_id'];
            }, array_filter($request['menu_ids'], function ($item) {
                return $item['status'] == 1;
            }));
            $parentList = [];
            foreach ($menuIds as $menuId) {
                $parent = MenuMaster::select('parent_id')->where('id', $menuId)->first();
                if ($parent) {
                    $parentList[] = [
                        'menu_id' => $parent->parent_id,
                        'status' => 1
                    ];
                }
            }
            $requestData = $request->all();
            $combinedArray = array_merge($requestData['menu_ids'], $parentList);
            if ($requestData && isset($combinedArray)) {
                foreach ($combinedArray as $menu_ids) {
                    $roleMenuMapping['role_id'] = $requestData['role_id'] ?? null;
                    $roleMenuMapping['menu_id'] = $menu_ids['menu_id'];
                    $roleMenuMapping['is_active'] = (string) $menu_ids['status'];
                    $this->roleRepo->saveMenu($roleMenuMapping);
                }
                return $this->responseJson(
                    config('crm/http-status.add.status'),
                    config('crm/http-status.add.message'),
                    config('crm/http-status.add.code'),
                    []
                );
            } else {
                return $this->responseJson(
                    config('crm/http-status.oops.status'),
                    config('crm/http-status.oops.message'),
                    config('crm/http-status.oops.code'),
                    []
                );
            }
        } catch (Throwable | ClientException $throwable) {
            throw new Throwable(Log::info("Service : RoleService , Method : saveMenu : %s" . $throwable->__toString()));
        }
    }
    /**
     * list particular role menus.
     *
     * @param
     * @return void
     */
    public function listMenuMapping(Request $request)
    {
        try {
            $menuList =  $this->roleRepo->roleMenuList($request);
            return $this->responseJson(
                config('crm/http-status.success.status'),
                config('crm/http-status.success.message'),
                config('crm/http-status.add.code'),
                $menuList
            );
        } catch (Throwable | ClientException $throwable) {
            throw new Throwable(Log::info("Service : RoleService , Method : listMenu : %s"
                . $throwable->__toString()));
        }
    }
    /**
     * role export.
     *
     * @param
     * @return void
     */
    public function export(Request $request)
    {
        try {
            $repository = new RoleRepository();
            $data['methodName'] = 'list';
            $data['fileName'] = 'Role-Menu-Report-';
            $data['moduleName'] = 'Role';
            return $this->exportData($request, $repository, $data);
        } catch (Throwable | ClientException $throwable) {
            Log::info("Service : RoleService , Method : export : %s"
                . $throwable->__toString());
        }
    }
}
