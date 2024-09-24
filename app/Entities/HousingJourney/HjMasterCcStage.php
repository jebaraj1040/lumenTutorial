<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjMasterCcStage extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $connection = 'mysql';
    protected $table = 'hj_master_cc_stage';
    protected $fillable = ['stage_id', 'name', 'handle',  'is_active', 'created_at', 'updated_at'];
}
