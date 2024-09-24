<?php

namespace App\Entities\Service;

use Illuminate\Database\Eloquent\Model;

class Bvncalls extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $connection = 'mysql';
    protected $table = 'bvn_calls';
    protected $fillable = [
        'store_code', 'business_name', 'customer_phone_number', 'bvn_call_status', 'recording_url'
    ];
}
