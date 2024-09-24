<?php

namespace App\Entities\Service;

use Illuminate\Database\Eloquent\Model;

class Microsite extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $connection = 'mysql';
    protected $table = 'microsite';
    protected $fillable = [
        'store_code', 'business_name', 'customer_name', 'mobile_number', 'pin_code',  'loan_amount'
    ];
}
