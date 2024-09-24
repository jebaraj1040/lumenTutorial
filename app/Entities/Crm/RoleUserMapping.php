<?php

namespace App\Entities\Crm;

use Illuminate\Database\Eloquent\Model;

class RoleUserMapping extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    protected $table = 'role_user_mapping';
    protected $fillable = [
        'role_id',
        'user_id',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by'
    ];
}
