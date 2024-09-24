<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjMappingApplicantRelationship extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $connection = 'mysql';
    public $timestamps = false;
    protected $table = 'hj_mapping_applicant_relationship';
    protected $fillable = ['lead_id', 'relationship_id', 'quote_id', 'application_id'];

    public function relationship()
    {
        return $this->hasOne(HjMasterRelationship::class, 'id', 'relationship_id');
    }
}
