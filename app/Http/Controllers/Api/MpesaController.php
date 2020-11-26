<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MpesaC2BTransactions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MpesaController extends Controller
{
    // controller constants
    public $apiKey, $apiSecret, $passKey, $lmoShortCode, $myPhoneNumber, $mainPB, $initiatorName, $initiatorSC;

    // constructor
    public function __construct()
    {
        $this->apiKey = env('MPESA_API_KEY');
        $this->apiSecret = env('MPESA_API_SECRET');
        $this->passKey = env('LMO_PASSKEY');
        $this->lmoShortCode = env('LMO_SHORTCODE');
        $this->myPhoneNumber = env('MY_PHONE_NUMBER');
        $this->mainPB = env('MAIN_PB');
        $this->initiatorName = env('INITIATOR_NAME');
        $this->initiatorSC = env('INITIATOR_SC');
    }

    // MISCELLANEOUS/HELPERS
    // access token
    public function generateAccessToken()
    {
        $credentials = base64_encode($this->apiKey.":".$this->apiSecret);

        $url = "https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Basic ".$credentials));
        curl_setopt($curl, CURLOPT_HEADER,false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $curl_response = curl_exec($curl);
        $access_token=json_decode($curl_response);
        return $access_token->access_token;
    }

    // STK PUSH
    // stk push password
    public function LMOPassword()
    {
        $lipa_time = Carbon::rawParse('now')->format('YmdHms');
        $passkey = $this->passKey;
        $BusinessShortCode = $this->lmoShortCode;
        $timestamp =$lipa_time;
        return base64_encode($BusinessShortCode.$passkey.$timestamp);
    }

    // initiate stk push
    public function stkInit(Request $request)
    {
        $url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json','Authorization:Bearer '.$this->generateAccessToken()));
        $curl_post_data = [
            //Fill in the request parameters with valid values
            'BusinessShortCode' => $this->lmoShortCode,
            'Password' => $this->LMOPassword(),
            'Timestamp' => Carbon::rawParse('now')->format('YmdHms'),
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $request->amount,
            'PartyA' => '254' . substr($request->phone_number, -9),
            'PartyB' => $this->lmoShortCode,
            'PhoneNumber' => '254' . substr($request->phone_number, -9),
            'CallBackURL' => 'https://3c3f3d3e0e09.ngrok.io/payments/stk_save',
            'AccountReference' => "Joe's Testbed",
            'TransactionDesc' => "Testing stk push on sandbox"
        ];
        $data_string = json_encode($curl_post_data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        return curl_exec($curl);
    }

    // save stk push
    public function stkSave(Request $request)
    {
        Log::channel('mpesa')->info($request->getContent());
        return response()->json(['message' => 'Request Processed...'], 200);
    }


    // C2B
    // validation response
    public function validationResponse($result_code, $result_description)
    {
        $result=json_encode(["ResultCode"=>$result_code, "ResultDesc"=>$result_description]);
        $response = new Response();
        $response->headers->set("Content-Type","application/json; charset=utf-8");
        $response->setContent($result);
        return $response;
    }

    // mpesa validation
    public function c2bValidation(Request $request)
    {
        $result_code = "0";
        $result_description = "Accepted validation request.";
        return $this->validationResponse($result_code, $result_description);
    }

    // c2b confirmation
    public function c2bConfirmation(Request $request)
    {
        $content = json_decode($request->getContent());
        Log::channel('mpesa')->info($request->getContent());
        $mpesa_transaction = new MpesaC2BTransactions();
        $mpesa_transaction->TransactionType = $content->TransactionType;
        $mpesa_transaction->TransID = $content->TransID;
        $mpesa_transaction->TransTime = $content->TransTime;
        $mpesa_transaction->TransAmount = $content->TransAmount;
        $mpesa_transaction->BusinessShortCode = $content->BusinessShortCode;
        $mpesa_transaction->BillRefNumber = $content->BillRefNumber;
        $mpesa_transaction->InvoiceNumber = $content->InvoiceNumber;
        $mpesa_transaction->OrgAccountBalance = $content->OrgAccountBalance;
        $mpesa_transaction->ThirdPartyTransID = $content->ThirdPartyTransID;
        $mpesa_transaction->MSISDN = $content->MSISDN;
        $mpesa_transaction->FirstName = $content->FirstName;
        $mpesa_transaction->MiddleName = $content->MiddleName;
        $mpesa_transaction->LastName = $content->LastName;
        $mpesa_transaction->save();
        // Responding to the confirmation request
        $response = new Response();
        $response->headers->set("Content-Type","text/xml; charset=utf-8");
        $response->setContent(json_encode(["C2BPaymentConfirmationResult" => "Success"]));
        return $response;
    }

    // register urls
    public function c2bRegisterUrls()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://sandbox.safaricom.co.ke/mpesa/c2b/v1/registerurl');
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json','Authorization: Bearer '. $this->generateAccessToken()));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(array(
            'ShortCode' => $this->mainPB,
            'ResponseType' => 'Completed',
            'ConfirmationURL' => "https://3c3f3d3e0e09.ngrok.io/payments/c2b_confirmation",
            'ValidationURL' => "https://3c3f3d3e0e09.ngrok.io/payments/c2b_validation"
        )));
        $curl_response = curl_exec($curl);
        echo $curl_response;
    }

    // maybe simulate...?

    // B2C
    // b2c
    public function b2cInit()
    {
        $url = 'https://sandbox.safaricom.co.ke/mpesa/b2c/v1/paymentrequest';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json','Authorization:Bearer '. $this->generateAccessToken()));


        $curl_post_data = array(
        'InitiatorName' => $this->initiatorName,
        'SecurityCredential' => $this->initiatorSC,
        'CommandID' => 'BusinessPayment',
        'Amount' => '5000',
        'PartyA' => $this->mainPB,
        'PartyB' => $this->mainPB,
        'Remarks' => 'Process the Files',
        'QueueTimeOutURL' => 'https://3c3f3d3e0e09.ngrok.io/payments/b2c_save',
        'ResultURL' => 'https://3c3f3d3e0e09.ngrok.io/payments/b2c_save',
        'Occasion' => 'NA'
        );

        $data_string = json_encode($curl_post_data);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

        $curl_response = curl_exec($curl);
        print_r($curl_response);

        echo $curl_response;
    }

    // b2c callback
    public function b2cSave(Request $request)
    {
        Log::channel('mpesa')->info($request->getContent());
        return response()->json(['message' => 'Request Processed...'], 200);
    }

}
