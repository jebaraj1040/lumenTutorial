<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjMasterEmploymentConstitutionType extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $connection = 'mysql';
    protected $table = 'hj_master_employment_constitution_type';
    protected $fillable = [
        'name', 'handle', 'master_id', 'display_name',
        'order_id', 'is_active', 'created_at', 'updated_at'
    ];
}
