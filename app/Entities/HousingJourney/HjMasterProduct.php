<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjMasterProduct  extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    protected $table = 'hj_master_product';
    protected $fillable = [
        'name', 'product_id', 'display_name', 'code', 'handle', 'processing_fee',
        'is_active', 'created_at', 'updated_at'
    ];
    public function application()
    {
        return $this->hasMany(HjApplication::class, 'id', 'master_product_id');
    }
}
