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
    $culqiPretashop =  new Culqi();
    $infoCheckout = $culqiPretashop->getCulqiInfoCheckout();

    $culqi = new Culqi\Culqi(array('api_key' => $infoCheckout['llave_secreta'] ));
    try {

      $args_charge = array(
            'amount' => (int)$infoCheckout['total'],
            'currency_code' => $infoCheckout['currency'],
            'email' => Tools::getValue("email"),
            'source_id' => Tools::getValue("token_id"),
            'capture' => false, 'enviroment' => $infoCheckout['enviroment_backend'],
            'antifraud_details' => array('device_finger_print_id'=>Tools::getValue("device"))
      );

      if(Tools::getValue("parameters3DS")!==FALSE){
          $args_charge['authentication_3DS'] = Tools::getValue("parameters3DS");
      }

      $culqi_charge = $culqi->Charges->create( $args_charge );

    } catch(Exception $e){
      die(Tools::jsonEncode($e->getMessage()));
    }

    die(Tools::jsonEncode($culqi_charge));
  }

}
