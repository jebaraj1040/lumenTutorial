<?php

namespace App\Entities\Crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuMaster extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    use SoftDeletes;
    protected $connection = 'mysql';
    protected $table = 'menu_master';
    protected $fillable = [
        'name',
        'handle',
        'slug',
        'is_parent',
        'parent_id',
        'is_active',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at',
        'deleted_by'
    ];
    public function roleMenu()
    {
        return $this->hasOne(RoleMenuMapping::class, 'menu_id', 'id');
    }
    public function userMenu()
    {
        return $this->hasOne(UserMenuMapping::class, 'menu_id', 'id');
    }
}
