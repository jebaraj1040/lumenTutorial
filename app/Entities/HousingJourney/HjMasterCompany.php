<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjMasterCompany extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    protected $table = 'hj_master_company';
    protected $fillable = [
        'code', 'name', 'handle', 'is_active', 'created_at', 'updated_at'
    ];
}
