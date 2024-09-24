<?php

namespace App\Entities\Crm;

use Illuminate\Database\Eloquent\Model;

class UserMenuMapping extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    protected $table = 'user_menu_mapping';
    protected $fillable = [
        'user_id',
        'menu_id',
        'is_active',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by'
    ];
}
