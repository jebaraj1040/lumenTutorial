<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjMasterProductStep extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    protected $table = 'hj_master_product_step';
    protected $fillable = [
        'name', 'handle', 'percentage',
        'is_active', 'created_at', 'updated_at',
    ];
}
