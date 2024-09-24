<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjMappingCcStage extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    protected $table = 'hj_mapping_cc_stage';
    protected $fillable = ['master_cc_stage_id', 'master_cc_sub_stage_id'];

    public function ccSubStage()
    {
        return $this->hasOne(HjMasterCcSubStage::class, 'id', 'master_cc_sub_stage_id');
    }

    public function ccStage()
    {
        return $this->hasOne(HjMasterCcStage::class, 'id', 'master_cc_stage_id');
    }

    public function masterCcStage()
    {
        return $this->belongsTo(HjMasterCcStage::class, 'master_cc_stage_id');
    }
}
