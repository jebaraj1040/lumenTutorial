<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjMappingProductStepCcStage extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    protected $table = 'hj_mapping_product_step_cc_stage';
    protected $fillable = ['master_product_step_id', 'master_cc_stage_id'];
}
