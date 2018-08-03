<?php 
  
    if( isset($_POST['sid']) && isset($_POST['mid']) )
    {
      	$data = [ 
      	 			'merchantID' => $_POST['mid'],
      	 			'securityCode' => $_POST['sid'],
      	 			'orderId' => $_POST['order_id'],
      	 			'amount' => $_POST['amount'],
      	 			'callBackUrl' => $_POST['callbackurl'],
      	 		];
        if(count($data))
        {
            sendTransaction($data); 
        }
        else
        {
            tt_redirect(siteUrl());
        }		
    }
    else
    { 
        tt_redirect(siteUrl());
    }
    function sendTransaction($data)
    {
        $params = array( 
            "authentication:1",
            'cache-control: no-cache',
            );

        $ch = curl_init();
        curl_setopt_array($ch, array(
        CURLOPT_URL => 'https://app.cointopay.com/MerchantAPI?Checkout=true',
        //CURLOPT_USERPWD => $this->apikey,
        CURLOPT_POSTFIELDS => 'SecurityCode='.$data['securityCode'].'&MerchantID='.$data['merchantID'].'&Amount=' . number_format($data['amount'], 2, '.', '').'&AltCoinID=1&output=json&inputCurrency=USD&CustomerReferenceNr='.$data['orderId'].'&transactionfailurl='.$data['callBackUrl'].'&transactionconfirmurl='.$data['callBackUrl'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => $params,
        CURLOPT_USERAGENT => 1,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC
        )
        );
        $redirect = curl_exec($ch);
        $results = json_decode($redirect);
        if($results->RedirectURL)
        {
           //fn_create_payment_form($results->RedirectURL, '', 'Cointopay', false);
            header("Location: ".$results->RedirectURL."");
        }
        echo $redirect;
        exit;
    }


    function siteUrl()
    {  

       // first get http protocol if http or https

       $base_url = (isset($_SERVER['HTTPS']) &&

       $_SERVER['HTTPS']!='off') ? 'https://' : 'http://';

       // get default website root directory

       $tmpURL = dirname(__FILE__);

       // when use dirname(__FILE__) will return value like this "C:\xampp\htdocs\my_website",

       //convert value to http url use string replace,

       // replace any backslashes to slash in this case use chr value "92"

       $tmpURL = str_replace(chr(92),'/',$tmpURL);

       // now replace any same string in $tmpURL value to null or ''

       // and will return value like /localhost/my_website/ or just /my_website/

       $tmpURL = str_replace($_SERVER['DOCUMENT_ROOT'],'',$tmpURL);

       // delete any slash character in first and last of value

       $tmpURL = ltrim($tmpURL,'/');

       $tmpURL = rtrim($tmpURL, '/');


       // check again if we find any slash string in value then we can assume its local machine

        if (strpos($tmpURL,'/'))
        {
            // explode that value and take only first value
            $tmpURL = explode('/',$tmpURL);
            $tmpURL = $tmpURL[0];
        }

        // now last steps
        // assign protocol in first value
        if ($tmpURL !== $_SERVER['HTTP_HOST'])
            // if protocol its http then like this
            $base_url .= $_SERVER['HTTP_HOST'].'/'.$tmpURL.'/';
        else
            // else if protocol is https
            $base_url .= $tmpURL.'/';
       // give return value
       return $base_url;
       }
       function tt_redirect($url)
       {
            header("Location: $url");
            exit();
       }
?>