<?php

namespace App\Services\HousingJourney;

use App\Services\Service;
use Illuminate\Http\Request;
use Throwable;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use App\Utils\CrmTrait;
use App\Repositories\HousingJourney\PaymentTransactionRepository;

class PaymentTransactionService  extends Service
{
     use CrmTrait;
     private $paymentTransactionData;

     /**
      * Create a new Service instance.
      *
      * @param
      * @return void
      */
     public function __construct(
          PaymentTransactionRepository $paymentTransactionData,
     ) {
          $this->paymentTransactionData = $paymentTransactionData;
     }
     /**
      * Create and Update  Menu.
      *
      * @param
      * @return void
      */
     public function getPaymentTransactionList(Request $request)
     {
          try {
               $paymentTransactionData = $this->paymentTransactionData->paymentTransactionRepoList($request);
               return $this->responseJson(
                    config('crm/http-status.success.status'),
                    config('crm/http-status.success.message'),
                    config('crm/http-status.success.code'),
                    $paymentTransactionData
               );
          } catch (Throwable  | ClientException $throwable) {
               throw new Throwable(Log::info("Service : PaymentTransactionService , Method : getPaymentTransactionList : %s" . $throwable->__toString()));
          }
     }
     public function export(Request $request)
     {
          try {
               $repository = new PaymentTransactionRepository();
               $data['methodName'] = 'paymentTransactionRepoList';
               $data['fileName'] = 'Payment-Transaction-Report-';
               $data['moduleName'] = 'Payment-Transaction';
               return $this->exportData($request, $repository, $data);
          } catch (Throwable | ClientException $throwable) {
               throw new (sprintf("ApplicationService list : %s", $throwable->__toString()));
          }
     }
}
