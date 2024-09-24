<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjImpression extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    protected $table = 'hj_impression';
    protected $fillable = ['lead_id', 'quote_id', 'master_product_id', 'master_product_step_id', 'detail_id', 'created_at', 'created_by', 'updated_at', 'updated_by'];
    public function lead()
    {
        return $this->hasOne(HjLead::class, 'id', 'lead_id');
    }
    public function application()
    {
        return $this->hasOne(HjApplication::class, 'quote_id', 'quote_id');
    }
    public function productName()
    {
        return $this->hasOne(HjMasterProduct::class, 'id', 'master_product_id');
    }
    public function stepName()
    {
        return $this->hasOne(HjMasterProductStep::class, 'id', 'master_product_step_id');
    }
}
