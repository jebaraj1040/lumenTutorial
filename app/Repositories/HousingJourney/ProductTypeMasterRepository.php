<?php

namespace App\Repositories\HousingJourney;

use App\Entities\HousingJourney\HjMasterProductType;
use App\Entities\HousingJourney\HjMasterPropertyType;
use App\Entities\HousingJourney\HjMappingProductType;
use Throwable;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Log;


class ProductTypeMasterRepository
{

    /**
     * get product journey type data
     *
     */
    public function view($name)
    {
        try {
            return HjMasterProductType::where('name', $name)->value('id');
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("ProductTypeMasterRepository view" . $throwable->getMessage());
        }
    }

    /**
     * upsert Product journey type  mapping 
     *
     * @param $request 
     */
    public function upsertProductTypeMasterMapping($request)
    {
        try {
            return HjMappingProductType::updateOrCreate([
                'master_product_id'
                => $request['master_product_id'], 'master_product_type_id' => $request['master_product_type_id']
            ], $request);
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("upsertProductTypeMasterMapping : " . $throwable->__toString());
        }
    }

    /**
     * get property type
     *
     */
    public function getPropertyType()
    {
        try {
            return HjMasterPropertyType::select('handle', 'name', 'id')->where('is_active', '1')->get();
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("ProductTypeMasterRepository getPropertyType" . $throwable->getMessage());
        }
    }
}
