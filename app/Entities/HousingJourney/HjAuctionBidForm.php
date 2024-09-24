<?php

namespace App\Entities\HousingJourney;

use Illuminate\Database\Eloquent\Model;

class HjAuctionBidForm extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $connection = 'mysql';
    protected $table = 'hj_auction_bid_form_details';
    protected $fillable = [
        'project_number',
        'project_name',
        'file_number',
        'pan_number',
        'name',
        'mobile_number',
        'email',
        'address',
        'pincode',
        'account_number',
        'ifsc_code',
        'bank_name',
        'branch_name',
        'property_item_number',
        'is_emd_remitted',
        'is_same_bank_details',
        'emd_account_number',
        'emd_ifsc_code',
        'emd_branch_name',
        'emd_bank_name',
        'consent',
    ];
}
