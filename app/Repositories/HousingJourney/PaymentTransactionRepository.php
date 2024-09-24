<?php

namespace App\Repositories\HousingJourney;

use Exception;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\HttpClientException;
use App\Entities\HousingJourney\HjPaymentTransaction;
use App\Entities\HousingJourney\HjApplication;
use App\Utils\CrmTrait;
use GuzzleHttp\Exception\ClientException;

class PaymentTransactionRepository
{
  use CrmTrait;
  /**
   * upsertPaymentData payment transaction data
   *
   */

  public function upsertPaymentData($request)
  {
    try {
      return HjPaymentTransaction::updateOrCreate(
        [
          'lead_id' => $request['lead_id'],
          'digital_transaction_no' => $request['digital_transaction_no'],
          'payment_transaction_id' => $request['payment_transaction_id']
        ],
        $request
      );
    } catch (Throwable | Exception | HttpClientException $throwable) {
      Log::info("PaymentTransactionRepository upsert PaymentData" . $throwable->__toString());
    }
  }
  /**
   * get payment transaction data
   *
   */
  public function getTransactionData($reqData)
  {
    try {
      return HjPaymentTransaction::with('payment')->where('quote_id', $reqData['quote_id'])->orderBy('id', 'desc')->first();
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("PaymentTransactionRepository getTransactionData" . $throwable->__toString());
    }
  }


  /**
   * fetch payment transaction data
   *
   */
  public function fetchTransactionData($reqData)
  {
    try {
      return HjPaymentTransaction::select('payment_gateway_id', 'amount', 'bank_name', 'payment_transaction_id', 'digital_transaction_no')->with('payment:id,name')->where('quote_id', $reqData['quote_id'])->where('payment_transaction_id', $reqData['payment_transaction_id'])
        ->first();
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("PaymentTransactionRepository fetchTransactionData" . $throwable->__toString());
    }
  }


  /**
   * get payment transaction data
   *
   */
  public function getPaymentTransactionData($reqData)
  {
    try {
      return HjPaymentTransaction::with('payment')->where('quote_id', $reqData['quote_id'])->get();
    } catch (Throwable  | HttpClientException $throwable) {
      Log::info("PaymentTransactionRepository getTransactionData" . $throwable->__toString());
    }
  }

  public function paymentTransactionRepoList($request, $offset = null)
  {
    try {
      $query = HjPaymentTransaction::query();
      $query = $this->applyFilter($query, $request);
      if ($request->search != '' && $request->search != 'null') {
        $keyword = $request->search;
        $query->where(function ($query) use ($keyword) {
          $query->orWhere('hj_payment_transaction.quote_id', 'LIKE', '%' . $keyword . '%');
          $query->orWhere('hj_payment_transaction.payment_transaction_id', $keyword);
          $query->orWhere('hj_payment_transaction.lead_id', $keyword);
          $query->orWhereHas('lead', function ($subquery) use ($keyword) {
            $subquery->where('mobile_number', $keyword);
          });
        });
      }
      $totalLength = $query->count();
      if ($request->action != 'download') {
        $skip = intval($request->skip);
        $limit = intval($request->limit);
        $query->skip($skip)->limit($limit);
      }
      if (empty($offset === false) && $offset != 'null' && $offset != '') {
        $limit = (int)env('EXPORT_EXCEL_LIMIT');
        $query->offset($offset)->limit($limit);
      }
      $getPaymentTransactionList = $query->select('*')
        ->with('lead:id,mobile_number')
        ->with('payment:id,name')
        ->orderBy('id', 'desc')
        ->get();

      if ($request->action == 'download') {
        foreach ($getPaymentTransactionList as $key => $item) {
          // Check if payment is loaded and not null
          if ($item->payment) {
            $getPaymentTransactionList[$key]['payment_gateway_id'] =  $item->payment->name;
          } else {
            $getPaymentTransactionList[$key]['payment_gateway_id'] = null;
          }
          // Check if lead is loaded and not null
          if ($item->lead) {
            $getPaymentTransactionList[$key]['lead_id'] =  $item->lead->mobile_number;
          } else {
            $getPaymentTransactionList[$key]['lead_id'] = null;
          }
          unset($getPaymentTransactionList[$key]['payment']);
          unset($getPaymentTransactionList[$key]['lead']);
        }
      }
      $paymentTransactionData['totalLength'] =  $totalLength;
      $paymentTransactionData['dataList'] = $getPaymentTransactionList;
      return $paymentTransactionData;
    } catch (Throwable  | ClientException $throwable) {
      throw new Throwable(Log::info(
        "Repository : PaymentTransactionRepository ,
          Method : paymentTransactionRepoList : %s"
          . $throwable->__toString()
      ));
    }
  }
}
