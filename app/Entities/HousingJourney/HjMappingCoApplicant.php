<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjMappingCoApplicant extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    protected $table = 'hj_mapping_co_applicant';
    public $timestamps = false;
    protected $fillable = ['lead_id', 'quote_id', 'co_applicant_id'];

    public function leadDetail()
    {
        return $this->hasOne(HjLead::class, 'id', 'lead_id');
    }
    public function coApplicantDetail()
    {
        return $this->hasOne(HjLead::class, 'id', 'co_applicant_id');
    }
}
