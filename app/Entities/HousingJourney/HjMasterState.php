<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjMasterState extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    protected $table = 'hj_master_state';
    protected $fillable = ['name', 'handle', 'is_active', 'created_at', 'updated_at'];
}
