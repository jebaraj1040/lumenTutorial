<?php

namespace App\Repositories\HousingJourney;

use GuzzleHttp\Exception\ClientException;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\HttpClientException;
use App\Entities\HousingJourney\HjMasterProduct;
use App\Entities\HousingJourney\HjMasterProductStep;

class MasterProductRepository
{
    /**
     * save product master
     *
     */
    public function save($data)
    {
        try {
            if ($data['product_id'] == null) {
                return HjMasterProduct::Create([
                    'code' => $data['code'],
                    'name' => $data['name'],
                    'handle' => $data['handle'],
                    'processing_fee' => $data['fee']
                ]);
            } else {
                return HjMasterProduct::where('id', $data['product_id'])
                    ->update([
                        'code' => $data['code'], 'name' => $data['name'], 'processing_fee' => $data['fee'],
                        'handle' => $data['handle'], 'is_active' => $data['is_active']
                    ]);
            }
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("MasterProductRepository save " . $throwable->__toString());
        }
    }
    /**
     * Edit master Product
     * @param $productId
     */
    public function edit($productId)
    {
        try {
            return HjMasterProduct::select('id', 'name', 'code', 'handle', 'processing_fee', 'is_active')
                ->where('id', $productId)->first();
        } catch (Throwable  | ClientException $throwable) {
            Log::info("edit : " . $throwable->__toString());
        }
    }
    /**
     * get product id
     *@param $name
     */
    public function getProductId($productCode)
    {
        try {
            return HjMasterProduct::where('code', $productCode)->value('id');
        } catch (Throwable | HttpClientException $throwable) {
            Log::info("getProductId " . $throwable->__toString());
        }
    }
    /**
     * List Master product from Table
     * @param $request
     */
    public function list($request)
    {
        try {
            $query = HjMasterProduct::query();
            if (empty($request->name === false) && $request->name != 'null' && $request->name != '') {
                $keyword = $request->name;
                $query->where(function ($query) use ($keyword) {
                    $query->orWhere('code', 'LIKE', '%' . $keyword . '%');
                    $query->orWhere('name', 'LIKE', '%' . $keyword . '%');
                });
            }
            if (empty($request->status === false) && ($request->status != 'null' && $request->status != '')) {
                $request->status = $request->status == 'Active' ? 1 : 0;
                $query->where('is_active', $request->status);
            }
            $totalLength = $query->count();
            if ($request->action != 'download') {
                $skip = intval($request->skip);
                $limit = intval($request->limit);
                $query->skip($skip)->limit($limit);
            }
            $product = $query->select('id', 'name', 'code', 'processing_fee', 'handle', 'is_active')->orderBy('id', 'desc')->get();
            $menuData['totalLength'] =  $totalLength;
            $menuData['dataList'] = $product;
            return $menuData;
        } catch (Throwable  | ClientException $throwable) {
            Log::info("Productlist : " . $throwable->__toString());
        }
    }
    /**
     * get list of master product & master product Step list
     *
     */
    public function filter()
    {
        try {
            $query = HjMasterProduct::query();
            $query->select('id', 'name');
            $filterdata['productname'] = $query->orderBy('id', 'desc')->get();
            $query = HjMasterProductStep::query();
            $query->select('id', 'name');
            $filterdata['productstepname'] = $query->orderBy('id', 'desc')->get();
            return $filterdata;
        } catch (Throwable  | HttpClientException $throwable) {
            Log::info("filter " . $throwable->__toString());
        }
    }
    public function delete($request)
    {
        try {
            $deleteProduct = auth('crm')->user()->id  ??  config('crm/user-constant.adminUserId');
            HjMasterProduct::where('id', $request->product_id)->update(['deleted_by' => $deleteProduct]);
            return HjMasterProduct::where('id', $request->product_id)->delete();
        } catch (Throwable  | ClientException $throwable) {
            Log::info("deleteProduct : " . $throwable->__toString());
        }
    }
    /**
     * get productid using code
     *
     */
    public function masterProductIdFetch($productCode)
    {
        try {
            return HjMasterProduct::where('code', $productCode)->where('is_active', 1)->value('id');
        } catch (Throwable  | ClientException $throwable) {
            Log::info("MasterProductRepository masterProductIdFetch " . $throwable->__toString());
        }
    }

    /**
     * get productid using code
     *
     */
    public function masterProductDataFetch($productCode)
    {
        try {
            return HjMasterProduct::select('id', 'name', 'code')->where('code', $productCode)->where('is_active', 1)->first();
        } catch (Throwable  | ClientException $throwable) {
            Log::info("MasterProductRepository masterProductIdFetch " . $throwable->__toString());
        }
    }

    /**
     * get Processing Fee
     *
     */
    public function getProcessingFee($id)
    {
        try {
            return HjMasterProduct::where('id', $id)->value('processing_fee');
        } catch (Throwable  | ClientException $throwable) {
            Log::info("MasterProductRepository getProcessingFee " . $throwable->__toString());
        }
    }
    /**
     * get product code using product id
     *
     */
    public function getProductCode($productId)
    {
        try {
            return HjMasterProduct::where('id', $productId)->where('is_active', 1)->value('code');
        } catch (Throwable  | ClientException $throwable) {
            Log::info("MasterProductRepository getProductCode " . $throwable->__toString());
        }
    }
}
