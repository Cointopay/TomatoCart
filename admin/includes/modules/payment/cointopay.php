<?php
/*
  $Id: cointopay.php $
  TomatoCart Open Source Shopping Cart Solutions
  http://www.tomatocart.com


  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License v2 (1991)
  as published by the Free Software Foundation.
*/

/**
 * The administration side of the authorizenet server integration method payment module
 */

  class osC_Payment_cointopay extends osC_Payment_Admin {

/**
 * The administrative title of the payment module
 *
 * @var string
 * @access private
 */
  var $_title;
  
/**
 * The code of the payment module
 *
 * @var string
 * @access private
 */

  var $_code = 'cointopay';
  
/**
 * The developers name
 *
 * @var string
 * @access private
 */

  var $_author_name = 'Axf Thalavi';
  
/**
 * The developers address
 *
 * @var string
 * @access private
 */  
  
  var $_author_www = 'http://www.facebook.com/686';
  
/**
 * The status of the module
 *
 * @var boolean
 * @access private
 */

  var $_status = false;
  
/**
 * Constructor
 */

  function osC_Payment_cointopay() {
    global $osC_Language;
    
    $this->_title = $osC_Language->get('payment_cointopay_title');
    $this->_description = $osC_Language->get('payment_cointopay_description');
    $this->_method_title = $osC_Language->get('payment_cointopay_method_title');
    $this->_status = (defined('MODULE_PAYMENT_cointopay_STATUS') && (MODULE_PAYMENT_cointopay_STATUS == '1') ? true : false);
    $this->_sort_order = (defined('MODULE_PAYMENT_cointopay_SORT_ORDER') ? MODULE_PAYMENT_cointopay_SORT_ORDER : null);
  }
  
/**
 * Checks to see if the module has been installed
 *
 * @access public
 * @return boolean
 */

  function isInstalled() {
    return (bool)defined('MODULE_PAYMENT_cointopay_STATUS');
  }
  
/**
 * Installs the module
 *
 * @access public
 * @see osC_Payment_Admin::install()
 */

  function install() {
    global $osC_Database, $osC_Language;
    
    parent::install();
    
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Cointopay', 'MODULE_PAYMENT_cointopay_STATUS', '-1', 'Do you want to accept Cointopay payments?', '6', '0', 'osc_cfg_set_boolean_value(array(1, -1))', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('MERCAHNT ID', 'MODULE_PAYMENT_cointopay_SELLER_ID', '', 'Your Cointopay Merchant ID.', '6', '0', now())");
   
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('SECURITY CODE', 'MODULE_PAYMENT_cointopay_SECRET_WORD', '', 'The Security Code you set on the Cointopay Site Management page.', '6', '0', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_cointopay_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '1', now())");
  }

/**
 * Return the configuration parameter keys in an array
 *
 * @access public
 * @return array
 */

  function getKeys() {
    if (!isset($this->_keys)) {
      $this->_keys = array('MODULE_PAYMENT_cointopay_STATUS',
                           'MODULE_PAYMENT_cointopay_SELLER_ID',
                           'MODULE_PAYMENT_cointopay_SECRET_WORD',
                           'MODULE_PAYMENT_cointopay_SORT_ORDER');
    }
  
    return $this->_keys;
 } 
}
?>

