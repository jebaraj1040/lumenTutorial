<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjPanHistory extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    protected $table = 'hj_pan_history';
    public $timestamps = false;
    protected $fillable = [
        'lead_id', 'quote_id', 'pan',
        'url', 'api_source', 'api_type', 'request', 'response', 'created_at'
    ];
}
