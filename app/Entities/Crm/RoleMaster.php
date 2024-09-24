<?php

namespace App\Entities\Crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoleMaster extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    use SoftDeletes;
    protected $connection = 'mysql';
    protected $table = 'role_master';
    protected $fillable = [
        'name',
        'handle',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at',
        'deleted_by'
    ];
}
