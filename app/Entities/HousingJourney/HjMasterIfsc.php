<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjMasterIfsc extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    protected $table = 'hj_master_ifsc';
    protected $fillable = [
        'bank_code', 'bank_name', 'location', 'ifsc', 'state', 'refpk', 'created_at', 'updated_at'
    ];
}
