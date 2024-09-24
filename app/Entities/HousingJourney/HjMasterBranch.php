<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjMasterBranch extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    protected $table = 'hj_master_branch';
    protected $fillable = [
        'name', 'handle',
        'code', 'is_active', 'created_at', 'updated_at'
    ];
}
