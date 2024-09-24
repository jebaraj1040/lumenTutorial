<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjMasterEmploymentSalaryMode extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $connection = 'mysql';
    protected $table = 'hj_master_employment_salary_mode';
    protected $fillable = ['name', 'handle', 'master_id', 'is_active', 'created_at', 'updated_at'];
}
