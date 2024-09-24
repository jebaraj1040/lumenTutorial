<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjMasterCcSubStage extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $connection = 'mysql';
    protected $table = 'hj_master_cc_sub_stage';
    protected $fillable = ['name', 'handle',  'is_active', 'priority', 'block_for_calling', 'created_at', 'updated_at'];
}
