<?php

namespace App\Repositories\Crm;

use GuzzleHttp\Exception\ClientException;
use Throwable;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Entities\Crm\RoleUserMapping;

class RoleUserRepository
{
    /**
     * get User Role by ID
     * @param $id
     */
    public function getUserRoleById($id)
    {
        try {
            return RoleUserMapping::where('user_id', $id)->first(['role_id']);
        } catch (Throwable | ClientException $throwable) {
            Log::info("menuList : " . $throwable->__toString());
        }
    }
}
