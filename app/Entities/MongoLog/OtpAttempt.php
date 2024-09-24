<?php

namespace App\Entities\MongoLog;

use MongoDB\Laravel\Eloquent\Model;

class OtpAttempt extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mongodb';
    protected $table = 'otp_attempt_failed';
    protected $fillable = [
        'mobile_number',
        'api_source_page',
        'master_product_id',
        'count',
        'status',
        'created_at',
        'updated_at',
    ];
}
