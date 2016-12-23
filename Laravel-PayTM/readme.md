# Laravel-PayTM 
### PayTM Payment Gateway Integration using Laravel Framework.

<p align="center"><a href="https://laravel.com" target="_blank"><img width="150"src="https://laravel.com/laravel.png"></a></p>

### Installation

For installing Laravel Framework and the required dependencies, please refer to the Laravel [Documentation](https://laravel.com/docs).


### 1. Create a new empty project

* Create an empty project and move inside that folder.

```sh
$ laravel new Laravel-PayTM
$ cd Laravel-PayTM
```


### 2. Open `.env`  file

* Paste the PayTM Staging configuration given below and save it.
* You can modify according to your needs.
* In Production environment, `PAYTM_ENVIRONMENT` get changed to`PROD` and all the transaction URLs(`PAYTM_TXN_URL,PAYTM_REFUND_URL,PAYTM_STATUS_QUERY_URL`) points to secure URLs `secure.paytm.in`.

```sh
PAYTM_ENVIRONMENT=TEST
PAYTM_MERCHANT_KEY=xxxxxxxxxxxxxxxxxxxxxxxxxxxx
PAYTM_MERCHANT_MID=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
PAYTM_MERCHANT_WEBSITE=WEB_STAGING
PAYTM_INDUSTRY_TYPE_ID=Retail
PAYTM_CHANNEL_ID=WEB
PAYTM_TXN_URL=https://pguat.paytm.com/oltp-web/processTransaction
PAYTM_REFUND_URL=https://pguat.paytm.com/oltp/HANDLER_INTERNAL/REFUND
PAYTM_STATUS_QUERY_URL=https://pguat.paytm.com/oltp/HANDLER_INTERNAL/TXNSTATUS
```

### 3. Move inside `app` folder
* Move inside `app` folder and create a new class file called `Paytm.php`.
```sh
$ Laravel-PayTM\cd app
```
*  Paste the PayTM Encryption/Decryption Code given below and save it.
*  You can choose your own approach of handling PayTM encryption code, below given code is for sample use.

```sh
<?php
namespace App;

class Paytm{

	public	function encrypt_e($input, $ky) {
		$key = $ky;
		$size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, 'cbc');
		$input = Paytm::pkcs5_pad_e($input, $size);
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', 'cbc', '');
		$iv = "@@@@&&&&####$$$$";
		mcrypt_generic_init($td, $key, $iv);
		$data = mcrypt_generic($td, $input);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		$data = base64_encode($data);
		return $data;
	}

	public function decrypt_e($crypt, $ky) {

		$crypt = base64_decode($crypt);
		$key = $ky;
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', 'cbc', '');
		$iv = "@@@@&&&&####$$$$";
		mcrypt_generic_init($td, $key, $iv);
		$decrypted_data = mdecrypt_generic($td, $crypt);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		$decrypted_data = Paytm::pkcs5_unpad_e($decrypted_data);
		$decrypted_data = rtrim($decrypted_data);
		return $decrypted_data;
	}

	public function pkcs5_pad_e($text, $blocksize) {
		$pad = $blocksize - (strlen($text) % $blocksize);
		return $text . str_repeat(chr($pad), $pad);
	}

	public function pkcs5_unpad_e($text) {
		$pad = ord($text{strlen($text) - 1});
		if ($pad > strlen($text))
			return false;
		return substr($text, 0, -1 * $pad);
	}

	public function generateSalt_e($length) {
		$random = "";
		srand((double) microtime() * 1000000);

		$data = "AbcDE123IJKLMN67QRSTUVWXYZ";
		$data .= "aBCdefghijklmn123opq45rs67tuv89wxyz";
		$data .= "0FGH45OP89";

		for ($i = 0; $i < $length; $i++) {
			$random .= substr($data, (rand() % (strlen($data))), 1);
		}

		return $random;
	}

	public function checkString_e($value) {
		$myvalue = ltrim($value);
		$myvalue = rtrim($myvalue);
		if ($myvalue == 'null')
			$myvalue = '';
		return $myvalue;
	}

	public function getChecksumFromArray($arrayList, $key, $sort=1) {
		if ($sort != 0) {
			ksort($arrayList);
		}
		$str = Paytm::getArray2Str($arrayList);
		$salt = Paytm::generateSalt_e(4);
		$finalString = $str . "|" . $salt;
		$hash = hash("sha256", $finalString);
		$hashString = $hash . $salt;
		$checksum = Paytm::encrypt_e($hashString, $key);
		return $checksum;
	}
	public function getChecksumFromString($str, $key) {
		
		$salt = Paytm::generateSalt_e(4);
		$finalString = $str . "|" . $salt;
		$hash = hash("sha256", $finalString);
		$hashString = $hash . $salt;
		$checksum = Paytm::encrypt_e($hashString, $key);
		return $checksum;
	}

	public function verifychecksum_e($arrayList, $key, $checksumvalue) {
		$arrayList = Paytm::removeCheckSumParam($arrayList);
		ksort($arrayList);
		$str = Paytm::getArray2Str($arrayList);
		$paytm_hash = Paytm::decrypt_e($checksumvalue, $key);
		$salt = substr($paytm_hash, -4);

		$finalString = $str . "|" . $salt;

		$website_hash = hash("sha256", $finalString);
		$website_hash .= $salt;

		$validFlag = "FALSE";
		if ($website_hash == $paytm_hash) {
			$validFlag = "TRUE";
		} else {
			$validFlag = "FALSE";
		}
		return $validFlag;
	}

	public function verifychecksum_eFromStr($str, $key, $checksumvalue) {
		$paytm_hash = Paytm::decrypt_e($checksumvalue, $key);
		$salt = substr($paytm_hash, -4);

		$finalString = $str . "|" . $salt;

		$website_hash = hash("sha256", $finalString);
		$website_hash .= $salt;

		$validFlag = "FALSE";
		if ($website_hash == $paytm_hash) {
			$validFlag = "TRUE";
		} else {
			$validFlag = "FALSE";
		}
		return $validFlag;
	}

	public function getArray2Str($arrayList) {
		$paramStr = "";
		$flag = 1;
		foreach ($arrayList as $key => $value) {
			if ($flag) {
				$paramStr .= Paytm::checkString_e($value);
				$flag = 0;
			} else {
				$paramStr .= "|" . Paytm::checkString_e($value);
			}
		}
		return $paramStr;
	}

	public function redirect2PG($paramList, $key) {
		$hashString = Paytm::getchecksumFromArray($paramList);
		$checksum = Paytm::encrypt_e($hashString, $key);
	}

	public function removeCheckSumParam($arrayList) {
		if (isset($arrayList["CHECKSUMHASH"])) {
			unset($arrayList["CHECKSUMHASH"]);
		}
		return $arrayList;
	}

	public function getTxnStatus($requestParamList) {
		return callAPI(PAYTM_STATUS_QUERY_URL, $requestParamList);
	}

	public function initiateTxnRefund($requestParamList) {
		$CHECKSUM = getChecksumFromArray($requestParamList,env('PAYTM_MERCHANT_KEY'),0);
		$requestParamList["CHECKSUM"] = $CHECKSUM;
		return callAPI(PAYTM_REFUND_URL, $requestParamList);
	}

	public function callAPI($apiURL, $requestParamList) {
		$jsonResponse = "";
		$responseParamList = array();
		$JsonData =json_encode($requestParamList);
		$postData = 'JsonData='.urlencode($JsonData);
		$ch = curl_init($apiURL);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);                                                                  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                         
		'Content-Type: application/json', 
		'Content-Length: ' . strlen($postData))                                                                       
		);  
		$jsonResponse = curl_exec($ch);   
		$responseParamList = json_decode($jsonResponse,true);
		return $responseParamList;
	}

}

```

### 4. Move inside `routes` folder
* Move inside `routes` folder and open the file `web.php`.
```sh
$ Laravel-PayTM\cd routes
$ Laravel-PayTM\routes\nano web.php
```


* Paste the below given code.

```sh
<?php
Route::post('/checkout', 'CartController@checkout')->name('checkout');
Route::post('/transaction/response', 'CartController@transaction')->name('transaction');
```

* Laravel Routes are used for handling GET & POST Requests.
* In the above code you can see there are two routes defined. `checkout`  and `transaction`.
* `checkout` is for handling business logic and initiating the payment process.
* `transaction` is for handling the response after the payment has been processed and PayTM gets redirected to the response URL.
* Response URL we are using is `http://localhost:8000/transaction/response`.
* The above url may not work in your case. For setting your response url you need to contact the PayTM team and set your response URL. 

### 4. Create `CartController` file
* You can create controller directly from Command Line.
```sh
$ Laravel-PayTM\php artisan make:controller CartController
Controller created successfully.
```

* Create two functions `checkout` and `transaction` for handling the request and response.
* Importing Paytm encryption class file -> `use App\Paytm`.
* Paste the below given code inside `CartController`.

```sh
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
				//Verify amount & order id received from Payment gateway with your applications order id and amount.

				//Process your business logic and store orders.
				
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

```

* Function `checkout` pass all the data to the `paytm.blade.php` view and then it  forwards to Payment Page.
* Function `transaction` is used for handling the response.
 
### 5. Create view `paytm.blade.php`

* Move inside `resources/views`.
* Create folder `payment` and move inside it.
* create file `paytm.blade.php`
* This file will initiate the transaction process and forward request to PayTM.


```sh
$ Laravel-PayTM\cd resources\views\
$ Laravel-PayTM\resources\views\mkdir payment
$ Laravel-PayTM\resources\views\cd payment
$ Laravel-PayTM\resources\views\payment\nano paytm.blade.php
```

*  Paste the below code inside `paytm.blade.php` and save it.
```sh
<!DOCTYPE html>
<html>
   <head>
      <title>Please Wait......</title>    
   </head>
   <body>


<h2 class="center-align grey-text darken-3">Please do not refresh this page...
</h2>


<form method="post" action="{{ env('PAYTM_TXN_URL') }}" name="f1">

           
         @foreach($paramList as $name => $value)
          <input type="hidden" name="{{ $name }}" value="{{$value }}">  
         @endforeach         
         
         <input type="hidden" name="CHECKSUMHASH" value="{{ $checkSum }}">
         {{ csrf_field() }}
       
      <script type="text/javascript">
         document.f1.submit();
      </script>
   
</form>

</body>
</html>

```



