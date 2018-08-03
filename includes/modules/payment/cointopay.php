<?php
  class osC_Payment_cointopay extends osC_Payment 
  {
    var $_title,
        $_code = 'cointopay',
        $_status = false,
        $_sort_order,
        $_order_id;

    // class constructor
    function osC_Payment_cointopay() 
    {
      global $osC_Database, $osC_Language, $osC_ShoppingCart;
  
      
      $this->_title = $osC_Language->get('payment_cointopay_title');
      $this->_method_title = $osC_Language->get('payment_cointopay_method_title');
      $this->_sort_order = MODULE_PAYMENT_cointopay_SORT_ORDER;
      $this->_status = ((MODULE_PAYMENT_cointopay_STATUS == '1') ? true : false);
  
    if ($this->_status === true) 
    {
        $this->order_status = (int)MODULE_PAYMENT_cointopay_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_cointopay_ORDER_STATUS_ID : (int)ORDERS_STATUS_PAID;

        if ((int)MODULE_PAYMENT_cointopay_ZONE > 0) 
        {
            $check_flag = false;

            $Qcheck = $osC_Database->query('select zone_id from :table_zones_to_geo_zones where geo_zone_id = :geo_zone_id and zone_country_id = :zone_country_id order by zone_id');
            $Qcheck->bindTable(':table_zones_to_geo_zones', TABLE_ZONES_TO_GEO_ZONES);
            $Qcheck->bindInt(':geo_zone_id', MODULE_PAYMENT_cointopay_ZONE);
            $Qcheck->bindInt(':zone_country_id', $osC_ShoppingCart->getBillingAddress('country_id'));
            $Qcheck->execute();

            while ($Qcheck->next()) 
            {
                if ($Qcheck->valueInt('zone_id') < 1) 
                {
                    $check_flag = true;
                    break;
                } 
                elseif ($Qcheck->valueInt('zone_id') == $osC_ShoppingCart->getBillingAddress('zone_id')) 
                {
                    $check_flag = true;
                    break;
                }
            }

            if ($check_flag == false) 
            {
                $this->_status = false;
            }
        }
    }
    $this->form_action_url =  $this->siteUrl()."/cointopay_send_transaction.php";
}
    function selection() {
      global $osC_Language;
       
      return array('id' => $this->_code,
                   'module' => $this->_method_title . (strlen($osC_Language->get('payment_cointopay_description')) > 0 ? ' (' . $osC_Language->get('payment_cointopay_description') . ')' : ''));
    }
    
    function confirmation() {
      $this->_order_id = osC_Order::insert();
    }

    function process_button() {
      global $osC_Customer, $osC_Currencies, $osC_ShoppingCart, $osC_Tax, $osC_Language;

      $process_button_string = '';
      $params = array(
                      'mid' => MODULE_PAYMENT_cointopay_SELLER_ID,
                      'sid' => MODULE_PAYMENT_cointopay_SECRET_WORD,
                      'amount' => $osC_Currencies->formatRaw($osC_ShoppingCart->getTotal()),
                      'order_id' => $this->_order_id,
                      'callbackurl' => osc_href_link(FILENAME_CHECKOUT, 'process=true', 'SSL', null, null, true));
      
      if ($osC_ShoppingCart->hasContents()) {
        $i = 1;
        
        foreach($osC_ShoppingCart->getProducts() as $product) {
          $tax = $osC_Tax->getTaxRate($product['tax_class_id'], $osC_ShoppingCart->getTaxingAddress('country_id'), $osC_ShoppingCart->getTaxingAddress('zone_id'));
          $price = $osC_Currencies->addTaxRateToPrice($product['final_price'], $tax);
            
          $params['c_prod_' . $i] = (int)$product['id'] . ',' . (int)$product['quantity'];
          $params['c_name_' . $i] =  $product['name'];
          $params['c_description_' . $i] = $product['name'];
          $params['c_price_' . $i] = $osC_Currencies->formatRaw($price);
        }
      }
      
      foreach ($params as $key => $value) {
        $process_button_string .= osc_draw_hidden_field($key, $value);
      }
      
      return $process_button_string;
    }

    function process() 
    {
        global $osC_Database, $osC_Currencies, $osC_ShoppingCart, $messageStack, $osC_Language;
      
        $paymentStatus = isset($_GET['status']) ? $_GET['status'] : 'failed'; 
        $notEngough = isset($_GET['notenough']) ? $_GET['notenough'] : '2';
        $transactionID = isset($_GET['TransactionID']) ? $_GET['TransactionID'] : '';
        $orderID = isset($_GET['CustomerReferenceNr']) ? $_GET['CustomerReferenceNr'] : '';
        if(isset($_GET['ConfirmCode']))
        {
            $data = [ 
                        'mid' =>  MODULE_PAYMENT_cointopay_SELLER_ID , 
                        'TransactionID' => $_GET['TransactionID'] ,
                        'ConfirmCode' => $_GET['ConfirmCode'] 
                    ];
            $response = $this->validateOrder($data);
       
            if($response->Status !== $_GET['status'])
            {
                echo "We have detected different order status. Your order has been halted.";
                exit;
            }
            elseif($response->CustomerReferenceNr == $_GET['CustomerReferenceNr'])
            {
                if ($paymentStatus == 'paid' &&  $notEngough == '0') 
                {
                    $order_status = 5; 
                    $comments = "Transaction Completed";
                    osC_Order::process($orderID, $order_status, "Transaction Status: ".$comments); 
                }
                elseif($paymentStatus == 'paid' &&  $notEngough == '1')
                {
                    $order_status = 1; 
                    $comments = "Transaction terminated, low balance";
                    osC_Order::process($orderID, $order_status, "Transaction Status: ".$comments); 
                }
                elseif($paymentStatus == 'failed')
                {
                    $order_status = 8; 
                    $comments = "Transaction failed";
                    osC_Order::process($orderID, $order_status, $comments); 
                }
                elseif($paymentStatus == 'waiting')
                {
                    $order_status = 8; 
                    $comments = "Transaction failed(manually called this Transaction payemnt)";
                    osC_Order::process($orderID, $order_status, $comments); 
                }
                else
                {
                    $comments =  "MD5 HASH MISMATCH, PLEASE CONTACT THE SELLER";
                    osC_Order::insertOrderStatusHistory($orderID, 8, "Transaction Status: ".$comments);
                    osc_redirect(osc_href_link(FILENAME_CHECKOUT, 'checkout&view=paymentInformationForm', 'SSL'));
                }
            }
        }
        else
        {
            echo "We have detected changes in order info. Your order has been halted.";
            exit;
        }
        
    }
    function  validateOrder($data)
    {
        
        $params = array( 
        "authentication:1",
        'cache-control: no-cache',
        );
        $ch = curl_init();
        curl_setopt_array($ch, array(
        CURLOPT_URL => 'https://app.cointopay.com/v2REAPI?',
        //CURLOPT_USERPWD => $this->apikey,
        CURLOPT_POSTFIELDS => 'MerchantID='.$data['mid'].'&Call=QA&APIKey=_&output=json&TransactionID='.$data['TransactionID'].'&ConfirmCode='.$data['ConfirmCode'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => $params,
        CURLOPT_USERAGENT => 1,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC
        )
        );
        $response = curl_exec($ch);
        $results = json_decode($response);
        if($results->CustomerReferenceNr)
        {
            return $results;
        }
        echo $response;
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
  }

  //http://localhost/tomato/checkout.php?process=true&CustomerReferenceNr=29&TransactionID=231327&status=waiting&notenough=0&ConfirmCode=-QFPEQM-BJWWU8SKBAOBB5RX_CIIZSZ0VYTP9SZKTCS&AltCoinID=1&MerchantID=14351&CoinAddressUsed=3C9fxEdTLmhYZZkCDhy5xuumWzc3v24JCB&SecurityCode=-903575721&inputCurrency=USD
?>
