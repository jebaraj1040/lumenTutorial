<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjpaymentGateway extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    protected $table = 'hj_payment_gateway';
    protected $fillable = [
        'name',
        'handle',
        'code',
        'is_active',
        'created_at',
        'updated_at'
    ];
}
