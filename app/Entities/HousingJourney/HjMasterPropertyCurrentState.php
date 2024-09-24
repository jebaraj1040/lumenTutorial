<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HjMasterPropertyCurrentState extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    use SoftDeletes;
    protected $connection = 'mysql';
    protected $table = 'hj_master_property_current_state';
    protected $fillable = ['name', 'handle', 'display_name', 'master_id', 'is_active', 'created_at', 'updated_at'];
}
