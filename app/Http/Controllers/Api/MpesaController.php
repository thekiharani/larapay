<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MpesaC2BTransactions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MpesaController extends Controller
{
    //
    public $apiKey, $apiSecret, $passKey, $lmoShortCode, $myPhoneNumber;

    public function __construct()
    {
        $this->apiKey = env('MPESA_API_KEY');
        $this->apiSecret = env('MPESA_API_SECRET');
        $this->passKey = env('LMO_PASSKEY');
        $this->lmoShortCode = env('LMO_SHORTCODE');
        $this->myPhoneNumber = env('MY_PHONE_NUMBER');
    }

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

    // stk push password
    public function LMOPassword()
    {
        $lipa_time = Carbon::rawParse('now')->format('YmdHms');
        $passkey = $this->passKey;
        $BusinessShortCode = $this->lmoShortCode;
        $timestamp =$lipa_time;
        $lipa_na_mpesa_password = base64_encode($BusinessShortCode.$passkey.$timestamp);
        return $lipa_na_mpesa_password;
    }

    // stk push
    public function customerSTKPush()
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
            'Amount' => 5,
            'PartyA' => $this->myPhoneNumber,
            'PartyB' => $this->lmoShortCode,
            'PhoneNumber' => $this->myPhoneNumber,
            'CallBackURL' => 'https://f58ac71a5955.ngrok.io/payments/stk_save',
            'AccountReference' => "Joe's Testbed",
            'TransactionDesc' => "Testing stk push on sandbox"
        ];
        $data_string = json_encode($curl_post_data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        return curl_exec($curl);
    }

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
        $content=json_decode($request->getContent());
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
        $response->setContent(json_encode(["C2BPaymentConfirmationResult"=>"Success"]));
        return $response;
    }

}
