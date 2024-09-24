<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;
use App\Entities\HousingJourney\HjLead;
use App\Entities\HousingJourney\HjPersonalDetail;

class HjMappingPanApplicant extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    protected $table = 'hj_mapping_pan_applicant';
    public $timestamps = false;
    protected $fillable = ['lead_id', 'quote_id', 'personal_detail_id'];

    public function leadDetail()
    {
        return $this->hasOne(HjLead::class, 'id', 'lead_id');
    }
    public function personalDetail()
    {
        return $this->hasOne(HjPersonalDetail::class, 'id', 'personal_detail_id');
    }
}
