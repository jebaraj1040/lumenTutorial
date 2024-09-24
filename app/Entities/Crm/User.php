<?php

namespace App\Entities\Crm;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Entities\Crm\RoleUserMapping;
use App\Entities\Crm\MenuMaster;

class User extends Model implements JWTSubject, AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    use SoftDeletes;
    protected $connection = 'mysql';
    protected $table = 'user';
    protected $fillable = [
        'email',
        'user_name',
        'password',
        'profile_path',
        'first_name',
        'middle_name',
        'last_name',
        'phone_number',
        'is_active',
        'created_by',
        'created_at',
        'updated_at',
        'updated_by',
        'deleted_at',
        'deleted_by'
    ];
    protected $hidden = [];
    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    public function role()
    {
        return $this->hasOneThrough(RoleMaster::class, RoleUserMapping::class, 'user_id', 'id', 'id', 'role_id');
    }
    public function menu()
    {
        return $this->hasManyThrough(MenuMaster::class, UserMenuMapping::class, 'user_id', 'id', 'id', 'menu_id');
    }
}
