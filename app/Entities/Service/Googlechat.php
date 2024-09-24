<?php

namespace App\Entities\Service;

use Illuminate\Database\Eloquent\Model;

class Googlechat extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */

    protected $connection = 'mysql';
    protected $table = 'google_chat';
    protected $fillable = [
        'store_code', 'business_name', 'user_name', 'phone_number', 'email_address',  'chatbot_option', 'tags'
    ];
}
