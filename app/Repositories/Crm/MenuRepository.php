<?php

namespace App\Repositories\Crm;

use GuzzleHttp\Exception\ClientException;
use Throwable;
use Illuminate\Support\Facades\Log;
use App\Entities\Crm\MenuMaster;
use App\Entities\Crm\RoleUserMapping;
use App\Entities\Crm\UserMenuMapping;

class MenuRepository
{
    /**
     * save menu to table
     * @param $request
     */
    public function save($request, $menuId = null)
    {
        try {
            $menuName = $request['name'];
            $existingMenu = MenuMaster::where('name', $menuName)->first();
            if ($existingMenu && $menuId == null) {
                return $existingMenu->name;
            }
            if ($existingMenu && $menuId != null) {
                $existingMenus = MenuMaster::where('id', $menuId)->first();
                if ($existingMenu->name != $existingMenus->name) {
                    return $existingMenu->name;
                }
            }
            $request['is_active'] = ($request['is_active'] == '1') ? '1' : '0';
            if ($menuId != null) {
                return MenuMaster::where('id', $menuId)->update($request);
            } else {
                return MenuMaster::create($request);
            }
        } catch (Throwable | ClientException $throwable) {
            Log::info("saveMenu : " . $throwable->__toString());
        }
    }
    /**
     * list menu from table
     * @param $request
     */
    public function list($request, $offset = null)
    {
        try {
            $query = MenuMaster::query();
            if (empty($request->name === false) && $request->name != 'null' && $request->name != '') {
                $query->where('menu_master.name', 'LIKE', '%' . $request->name . '%');
            }
            if (
                empty($request->status === false) && ($request->status != 'null' && $request->status != '')
                && ($request->status == 'Active'  || $request->status == 'Inactive')
            ) {
                $request->status = $request->status == 'Active' ? 1 : 0;
                $query->where('menu_master.is_active', $request->status);
            }
            if (
                empty($request->menuType === false) && ($request->menuType != 'null' && $request->menuType != '')
                && ($request->menuType == 'Parent'  || $request->menuType == 'Child')
            ) {
                $request->menuType = $request->menuType == 'Parent' ? 1 : 0;
                $query->where('menu_master.is_parent', $request->menuType);
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
            $menuList = $query->select(
                'menu_master.id',
                'menu_master.name as name',
                'p.name as parent_id',
                'menu_master.handle',
                'menu_master.slug',
                'menu_master.is_parent',
                'menu_master.is_active'
            )
                ->leftJoin('menu_master as p', 'menu_master.parent_id', '=', 'p.id')->orderBy('id', 'desc')
                ->get();

            $menuData['totalLength'] =  $totalLength;
            $menuData['dataList'] = $menuList;
            return $menuData;
        } catch (Throwable  | ClientException $throwable) {
            Log::info("listMenu : " . $throwable->__toString());
        }
    }
    /**
     * list menu from table
     * @param $request
     */
    public function parentList($request)
    {
        try {
            $query = MenuMaster::query();
            if (empty($request->menu_id === false) && $request->menu_id != 'null' && $request->menu_id != '') {
                $query->where('id', '!=', $request->menu_id);
            }
            return $query->where('is_parent', 1)
                ->orderBy('id', 'desc')->get();
        } catch (Throwable  | ClientException $throwable) {
            Log::info("listMenu : " . $throwable->__toString());
        }
    }
    /**
     * list menu in role
     * @param $request
     */
    public function roleMenulist()
    {
        try {
            $query = MenuMaster::query();
            return $query->select('id', 'name', 'slug', 'is_parent', 'parent_id', 'handle', 'is_active')
                ->where('is_active', '1')
                ->where('is_parent', '0')
                ->orderBy('id', 'desc')
                ->get();
        } catch (Throwable  | ClientException $throwable) {
            Log::info("roleMenulist : " . $throwable->__toString());
        }
    }
    /**
     * list menu for user
     * @param $request
     */
    public function userMenuList($request)
    {
        try {
            $userId = auth('crm')->user()->id ?? config('crm/user-constant.adminUserId');
            $roleId = RoleUserMapping::select('role_id')->where('user_id', $userId)->first();
            $roleid = $request->roleId ? $request->roleId : $roleId->role_id;
            $userMenuList = [];
            if ($roleid) {
                $userMenuList = MenuMaster::with(['roleMenu' => function ($query) use ($roleid) {
                    $query->where('role_id', $roleid)->where('is_active', '1');
                }])->select(
                    'name',
                    'id',
                    'slug',
                    'handle',
                    'is_parent',
                    'parent_id',
                    'is_active'
                )->where('is_active', '1')->where('is_parent', '0')->get();
            }
            return $userMenuList;
        } catch (Throwable  | ClientException $throwable) {
            Log::info("userMenuList : " . $throwable->__toString());
        }
    }
    /**
     * delete menu
     * @param $request
     */
    public function edit($menuId)
    {
        try {
            return MenuMaster::select(
                'menu_master.id as id',
                'menu_master.name as name',
                'p.name as parent_id',
                'menu_master.parent_id as parentId',
                'menu_master.handle',
                'menu_master.is_parent as is_parent',
                'menu_master.is_active',
                'menu_master.slug'
            )
                ->leftJoin('menu_master as p', 'menu_master.parent_id', '=', 'p.id')
                ->where('menu_master.id', $menuId)->first();
        } catch (Throwable  | ClientException $throwable) {
            Log::info("editMenu : " . $throwable->__toString());
        }
    }
    /**
     * edit menu
     * @param $request
     */
    public function delete($request)
    {
        try {
            $deleteMenu = auth('crm')->user()->id  ??  config('crm/user-constant.adminUserId');
            MenuMaster::where('id', $request->menu_id)->update(['deleted_by' => $deleteMenu]);
            return MenuMaster::where('id', $request->menu_id)->delete();
        } catch (Throwable  | ClientException $throwable) {
            Log::info("deleteMenu : " . $throwable->__toString());
        }
    }
    /**
     * user based menu
     * @param $request
     */
    public function userBasedMenu($menuId)
    {
        try {
            $userId = auth('crm')->user()->id  ??  config('crm/user-constant.adminUserId');
            return UserMenuMapping::select('is_active')->where('user_id', $userId)->where('menu_id', $menuId)->first();
        } catch (Throwable  | ClientException $throwable) {
            Log::info("userBasedMenu : " . $throwable->__toString());
        }
    }
    public function orderstatus($request)
    {
        try {
            $query = MenuMaster::query();
            if ($request->parent_id === null) {
                $query->where('is_parent', $request->is_parent);
            } else {
                $query->where('parent_id', $request->parent_id);
            }
            return $query->select('id', 'name', 'order_number')->orderBy('order_number')->get();
        } catch (Throwable  | ClientException $throwable) {
            Log::info("orderstatus : " . $throwable->__toString());
        }
    }
    public function orderupdate($request)
    {
        try {
            $updateData = $request['updateData'];
            if (is_array($updateData)) {
                foreach ($updateData as $item) {
                    if (is_array($item)) {
                        $menu = MenuMaster::find($item['id']);
                        if ($menu) {
                            $menu->order_number = $item['order_number'];
                            $menu->save();
                        }
                    }
                }
            } else {
                Log::info('Error: Something went worng ');
            }
        } catch (Throwable  | ClientException $throwable) {
            Log::info("orderupdate : " . $throwable->__toString());
        }
    }
}
