<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjMasterApiLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    protected $table = 'hj_master_api_log';
    protected $fillable = ['api_source', 'api_type', 'request', 'response', 'url', 'api_status', 'created_at', 'updated_at'];
}
