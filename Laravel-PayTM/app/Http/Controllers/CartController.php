<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Paytm;


class CartController extends Controller
{
   


    public function checkout(){     
	
	/** 
	
		Handling all the business logic (adding products, totalling etc).

	**/                 


    $checkSum = "";
    $paramList = array();
    $ORDER_ID = mt_rand(1000,5000).time(); // UNIQUE ORDER ID 
    $CUST_ID = 1; //CUSTOMER ID
    $INDUSTRY_TYPE_ID = env('PAYTM_INDUSTRY_TYPE_ID');
    $CHANNEL_ID = env('PAYTM_CHANNEL_ID');
    $EMAIL = 'paytm@paytm.com'; //EMAIL OF CUSTOMER
    $TXN_AMOUNT = $total; // TOTAL AMOUNT


    // Create an array having all required parameters for creating checksum.
    $paramList["MID"] = env('PAYTM_MERCHANT_MID');
    $paramList["ORDER_ID"] = $ORDER_ID;
    $paramList["CUST_ID"] = $CUST_ID;
    $paramList["INDUSTRY_TYPE_ID"] = $INDUSTRY_TYPE_ID;
    $paramList["CHANNEL_ID"] = $CHANNEL_ID;
    $paramList["TXN_AMOUNT"] = $TXN_AMOUNT;
    $paramList["WEBSITE"] = env('PAYTM_MERCHANT_WEBSITE');
    $paramList["EMAIL"] = $EMAIL; //Email ID of customer

    $paytm_encrypt = new Paytm(); // Creating new Object
    $checkSum = $paytm_encrypt->getChecksumFromArray($paramList,env('PAYTM_MERCHANT_KEY'));  

   
   	// Passing all the data inside a view.             
    return view('payment.paytm', compact('checkSum','paramList'));
       
                   
    }


    public function transaction(Request $request)
    {
            
    		header("Pragma: no-cache");
			header("Cache-Control: no-cache");
			header("Expires: 0");

            $paytm_encrypt = new Paytm(); // Creating new Object
            $paytmChecksum = "";
            $paramList = array();
            $isValidChecksum = "FALSE";
            $paramList = $_POST;
            $paytmChecksum = isset($_POST["CHECKSUMHASH"]) ? $_POST["CHECKSUMHASH"] : ""; //Sent by Paytm pg
            //Verify all parameters received from Paytm pg to your application. Like MID received from paytm pg is same as your application’s MID, TXN_AMOUNT and ORDER_ID are same as what was sent by you to Paytm PG for initiating transaction etc.
            $isValidChecksum = $paytm_encrypt->verifychecksum_e($paramList, 
                env('PAYTM_MERCHANT_KEY'), $paytmChecksum); //will return TRUE or FALSE string.
            
            if($isValidChecksum == "TRUE") {
				echo "<b>Checksum matched and following are the transaction details:</b>" . "<br/>";
			if ($_POST["STATUS"] == "TXN_SUCCESS") {
				echo "<b>Transaction status is success</b>" . "<br/>";
				//Process your transaction here as success transaction.
				//Verify amount & order id received from Payment gateway with your application's order id and amount.

				//Process your business logic and store orders in Database.
				
			}
			else {
				echo "<b>Transaction status is failure</b>" . "<br/>";
			}
			if (isset($_POST) && count($_POST)>0 )
			{ 
				foreach($_POST as $paramName => $paramValue) {
						echo "<br/>" . $paramName . " = " . $paramValue;
				}
			}
			
		}
		else {
			echo "<b>Checksum mismatched.</b>";
			//Process transaction as suspicious.
		}
 
	}

}
