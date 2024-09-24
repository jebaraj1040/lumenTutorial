<?php

namespace App\Entities\HousingJourney;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

class WebSubmission extends Model
{
    use Authenticatable, Authorizable, HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    protected $table = 'website_submissions';
    protected $fillable = [
        'name',
        'mobile_number',
        'email',
        'pincode_id',
        'master_product_id',
        'loan_amount',
        'source_page',
        'is_verified',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'utm_params',
        'is_assisted',
        'partner_code',
        'created_at',
        'updated_at'
    ];
    public function pincodeData()
    {
        return $this->hasOne(HjMasterPincode::class, 'id', 'pincode_id');
    }
    public function masterProductData()
    {
        return $this->hasOne(HjMasterProduct::class, 'id', 'master_product_id');
    }
}
