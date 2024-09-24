<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjMasterIndustryType extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $connection = 'mysql';
    protected $table = 'hj_master_industry_type';
    protected $fillable = ['name', 'handle', 'industry_segment_id', 'is_active', 'created_at', 'updated_at'];
}
