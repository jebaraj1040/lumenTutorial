<?php

namespace App\Repositories\Crm;

use App\Entities\Crm\MenuMaster;
use GuzzleHttp\Exception\ClientException;
use Throwable;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Entities\Crm\RoleMaster;
use App\Entities\Crm\RoleMenuMapping;
use App\Entities\Crm\RoleUserMapping;

class RoleRepository
{
    /**
     * save role to table
     * @param $request
     */
    public function save($request, $roleId = null)
    {
        try {
            if ($roleId != null) {
                return RoleMaster::where('id', $roleId)->update($request);
            } else {
                $existingRole = RoleMaster::where('name', $request['name'])->count();
                if ($existingRole == 0) {
                    return RoleMaster::create($request);
                } else {
                    $role['status'] = 422;
                    $role['msg'] = "This Role is already taken (" . $request['name'] . ")
                    . Please use different Role.";
                    return $role;
                }
            }
        } catch (Throwable  | ClientException $throwable) {
            Log::info("saveRole : " . $throwable->__toString());
        }
    }
    /**
     * list role from table
     * @param $request
     */
    public function list($request, $offset = null)
    {
        try {
            $query = RoleMaster::query();
            if (empty($request->name === false) && $request->name != 'null' && $request->name != '') {
                $query->where('name', 'LIKE', '%' . $request->name . '%');
            }
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
            $roleList = $query->select('id', 'name', 'handle')->orderBy('id', 'desc')->get();
            $roleData['totalLength'] =  $totalLength;
            $roleData['dataList'] = $roleList;
            return $roleData;
        } catch (Throwable  | ClientException $throwable) {
            Log::info("listRole : " . $throwable->__toString());
        }
    }
    /**
     * edit role
     * @param $request
     */
    public function edit($request)
    {
        try {
            return RoleMaster::select('id', 'name', 'handle')
                ->where('id', $request->role_id)
                ->orderBy('id', 'desc')
                ->first();
        } catch (Throwable  | ClientException $throwable) {
            Log::info("editRole : " . $throwable->__toString());
        }
    }
    /**
     * delete role
     * @param $request
     */
    public function delete($request)
    {
        try {
            $deleteRole = auth('crm')->user()->id ?? config('crm/user-constant.adminUserId');
            $userCount = RoleUserMapping::where('role_id', $request->role_id)->count();
            if ($userCount == 0) {
                RoleMaster::where('id', $request->role_id)->update(['deleted_by' => $deleteRole]);
                return RoleMaster::where('id', $request->role_id)->delete();
            } else {
                return "RoleNotDeleted";
            }
        } catch (Throwable  | ClientException $throwable) {
            Log::info("deleteRole : " . $throwable->__toString());
        }
    }
    /**
     * save role menu
     * @param $request
     */
    public function saveMenu($request)
    {
        try {
            $existMenu = RoleMenuMapping::where('role_id', $request['role_id'])
                ->where('menu_id', $request['menu_id'])
                ->count();
            if ($existMenu == 0) {
                RoleMenuMapping::create($request);
            } else {
                RoleMenuMapping::where('role_id', $request['role_id'])
                    ->where('menu_id', $request['menu_id'])
                    ->update(['is_active' => $request['is_active']]);
            }
        } catch (Throwable  | ClientException $throwable) {
            Log::info("saveMenu : " . $throwable->__toString());
        }
    }
    /**
     * list particular menus based on role
     * @param $request
     */
    public function sideBarMenulist()
    {
        try {
            $userId = auth('crm')->user()->id ?? config('crm/user-constant.adminUserId');
            $roleId = RoleUserMapping::select('role_id')->where('user_id', $userId)->first();
            $sideBarMenulist = [];
            if ($roleId) {
                $sideBarMenulist = MenuMaster::with(['roleMenu' => function ($query) use ($roleId) {
                    $query->where('role_id', $roleId->role_id)->where('is_active', '1');
                }, 'userMenu' => function ($query) use ($userId) {
                    $query->where('user_id', $userId)->where('is_active', '1');
                }])->select('name', 'id', 'slug', 'handle', 'is_parent', 'parent_id', 'is_active', 'order_number')
                ->where('is_active', '1')->orderBy('order_number')->get();
            }
            $filteredSideBarMenulist = $sideBarMenulist->filter(function ($item) {
                if ($item) {
                    return !is_null($item->roleMenu)  &&
                        !is_null($item->userMenu);
                }
                return false;
            });
            return $filteredSideBarMenulist->values();
        } catch (Throwable  | ClientException $throwable) {
            Log::info("sideBarMenulist : " . $throwable->__toString());
        }
    }
    /**
     * list particular role menus
     * @param $request
     */
    public function roleMenuList($request)
    {
        try {
            return  RoleMenuMapping::with(['roleName' => function ($query) {
                $query->select('name', 'id', 'handle');
            }])->select('menu_id', 'role_id', 'is_active')->where('role_id', $request->role_id)->get();
        } catch (Throwable  | ClientException $throwable) {
            Log::info("menuList : " . $throwable->__toString());
        }
    }
    /**
     * get role data
     * @param $id
     */
    public function getRoleById($id)
    {
        try {
            return  RoleMaster::where('id', $id)->first(['id', 'name', 'handle']);
        } catch (Throwable  | ClientException $throwable) {
            Log::info("menuList : " . $throwable->__toString());
        }
    }
    /**
     * get admin role id
     * @param $id
     */
    public function getAdminRoleId()
    {
        try {
            return RoleMaster::where('handle', 'admin')->value('id');
        } catch (Throwable  | ClientException $throwable) {
            Log::info("getAdminRoleId : " . $throwable->__toString());
        }
    }
}
