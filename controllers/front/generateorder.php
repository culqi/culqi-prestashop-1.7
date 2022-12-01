<?php

include_once dirname(__FILE__, 3) . '/libraries/culqi-php/lib/culqi.php';
include_once dirname(__FILE__, 3) . '/culqi.php';

class CulqiGenerateOrderModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        $this->ajax = false;
        $culqiPretashop =  new Culqi();
        $infoCheckout = $culqiPretashop->getCulqiInfoCheckout();
        $culqi = new Culqi\Culqi(array('api_key' => $infoCheckout['llave_secreta'] ));
        $phone = ($infoCheckout['address'][0]['phone']!='' and !is_null($infoCheckout['address'][0]['phone'])) ? $infoCheckout['address'][0]['phone'] : '999999999';
        $expiration_date = time() + (int)$infoCheckout['tiempo_exp'] * 60 * 60;
        $args_order = array(
             
            'amount' => (int)$infoCheckout['total'],
            'currency_code' => $infoCheckout['currency'],
            'description' => 'Venta desde Plugin Prestashop',
            'order_number' => 'pts-' . time(),
            'client_details' => array(
                'email' => isset($_POST["customer_email"]) ? $_POST["customer_email"] : $infoCheckout['customer']->email,
                'first_name' => isset($_POST["customer_firstname"]) ? $_POST["customer_firstname"] : $infoCheckout['customer']->firstname,
                'last_name' => isset($_POST["customer_lastname"]) ? $_POST["customer_lastname"] : $infoCheckout['customer']->lastname,
                'phone_number' => $phone
            ),
            'expiration_date' => $expiration_date,
            'confirm' => false,
            'enviroment' => $infoCheckout['enviroment_backend'],
            'metadata' => ["pts_order_id" => (string)$infoCheckout['orden'], "sponsor" => "prestashop"]
        );
 
        $culqi_order = $culqi->Orders->create( $args_order );
        die(Tools::jsonEncode($culqi_order->id));
    }
}