<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HjBreVersion extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    protected $table = 'hj_bre_version';
    protected $fillable = ['version', 'created_at'];
    public $timestamps = false;
}
