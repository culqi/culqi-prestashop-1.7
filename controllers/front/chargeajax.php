<?php

include_once dirname(__FILE__, 3) . '/libraries/culqi-php/lib/culqi.php';
include_once dirname(__FILE__, 3) . '/culqi.php';

class CulqiChargeAjaxModuleFrontController extends ModuleFrontController
{

  public function initContent()
  {
    parent::initContent();
    $this->ajax = true;
  }

  public function displayAjax()
  {
      error_reporting(E_ALL);
      ini_set('display_errors', '1');
    $culqiPretashop =  new Culqi();
    $infoCheckout = $culqiPretashop->getCulqiInfoCheckout();

    $order_id = Tools::getValue("ps_order_id");
    //var_dump($order_id); exit(1);
    $culqi = new Culqi\Culqi(array('api_key' => $infoCheckout['llave_secreta'] ));
    try {

      $args_charge = array(
            'amount' => (int)$infoCheckout['total'],
            'currency_code' => $infoCheckout['currency'],
            'email' => Tools::getValue("email"),
            'source_id' => Tools::getValue("token_id"),
            'capture' => true, 
            'enviroment' => $infoCheckout['enviroment_backend'],
            'antifraud_details' => array('device_finger_print_id'=>Tools::getValue("device")),
            'metadata' => ["pts_order_id" => (string)$order_id, "sponsor" => "prestashop"],
      );

      if(Tools::getValue("parameters3DS")!==FALSE){
          $args_charge['authentication_3DS'] = Tools::getValue("parameters3DS");
      }
      $culqi_charge = $culqi->Charges->create( $args_charge );
      $findorder = Db::getInstance()->ExecuteS("SELECT distinct * FROM " . _DB_PREFIX_ . "orders where id_order='". $order_id . "'");
      $reference = $findorder[0]['reference'];

      
      Db::getInstance()->ExecuteS("UPDATE SET transaction_id = '" . $culqi_charge . "' FROM ps_order_payment WHERE order_reference = '". $reference . "'");
      //$order_reference = $order_payment[0]["order_reference"];

      // $order = new Order($order_id);
      // $order_payment_collection = $order->getOrderPaymentCollection();
      // $order_payment = $order_payment_collection[0];
      // $order_payment->transaction_id = $culqi_charge;
      // $order_payment->update(); 

    } catch(Exception $e){
      die(Tools::jsonEncode($e->getMessage()));
    }

    die(Tools::jsonEncode($culqi_charge));
  }

}
