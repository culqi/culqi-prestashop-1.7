<?php

include_once dirname(__FILE__, 3) . '/libraries/culqi-php/lib/culqi.php';
Requests::register_autoloader();
include_once dirname(__FILE__, 3) . '/culqi.php';

class CulqiGenerateOrderModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        try {
            parent::initContent();
            $this->ajax = false;
            $culqiPretashop =  new Culqi();
            $infoCheckout = $culqiPretashop->getCulqiInfoCheckout();
            $culqi = new Culqi\Culqi(array('api_key' => $infoCheckout['llave_secreta'] ));
            $phone = ($infoCheckout['address'][0]['phone']!='' and !is_null($infoCheckout['address'][0]['phone'])) ? $infoCheckout['address'][0]['phone'] : false;
            if(!$phone) {
                $phone = $infoCheckout['address'][0]['phone_mobile'] ?: '999999999';
            }
            $expiration_date = time() + (int)$infoCheckout['tiempo_exp'] * 60 * 60;
            $args_order = array(
                 
                'amount' => (int)$infoCheckout['total'],
                'currency_code' => $infoCheckout['currency'],
                'description' => 'Venta desde Plugin Prestashop',
                'order_number' => 'pts-' . time(),
                'client_details' => array(
                    'email' => $infoCheckout['customer']->email,
                    'first_name' => $infoCheckout['customer']->firstname,
                    'last_name' => $infoCheckout['customer']->lastname,
                    'phone_number' => $phone
                ),
                'expiration_date' => $expiration_date,
                'confirm' => false,
                'enviroment' => $infoCheckout['enviroment_backend'],
                'metadata' => ["pts_order_id" => (string)$infoCheckout['orden'], "sponsor" => "prestashop"]
            );
     
            $culqi_order = $culqi->Orders->create( $args_order );
            $response = [
                'order_id' => $culqi_order->id,
                'amount' => (int)$infoCheckout['total']
            ];
            die(json_encode($response));
        } catch(Exception $e) {
            $response = [
                'order_id' => '',
                'amount' => (int)$infoCheckout['total']
            ];
            die(json_encode($response));
        }
    }
}