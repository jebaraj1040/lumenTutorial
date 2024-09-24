<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HjMasterProfessionalType extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    use SoftDeletes;
    protected $connection = 'mysql';
    protected $table = 'hj_master_professional_type';
    protected $fillable = ['name', 'handle', 'master_id', 'is_active', 'created_at', 'updated_at'];
}
