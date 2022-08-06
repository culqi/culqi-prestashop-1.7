<?php

include_once dirname(__FILE__, 3) . '/libraries/culqi-php/lib/culqi.php';
include_once dirname(__FILE__, 3) . '/culqi.php';

class CulqiGenerateOrderModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        parent::initContent();
        $this->ajax = false;

        
        $culqiPretashop =  new Culqi();
        $infoCheckout = $culqiPretashop->getCulqiInfoCheckout();
        //var_dump($infoCheckout['tiempo_exp']); exit(1);
        $culqi = new Culqi\Culqi(array('api_key' => $infoCheckout['llave_secreta'] ));
        $phone = ($infoCheckout['address'][0]['phone']!='' and !is_null($infoCheckout['address'][0]['phone'])) ? $infoCheckout['address'][0]['phone'] : '999999999';
        $expiration_date = time() + (int)$infoCheckout['tiempo_exp'] * 60 * 60;
        //var_dump($expiration_date); exit(1);
        $args_order = array(
             
            'amount' => (int)$infoCheckout['total'],
            'currency_code' => $infoCheckout['currency'],
            //'description' => $infoCheckout['descripcion'],
            'description' => 'Venta desde Plugin Prestashop',
            //'order_number' => (string)$infoCheckout['orden'] . $expiration_date,
            //'order_number' => 'pts-' . $expiration_date.date('Ymdihs'),
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
        //echo var_dump($culqi_order);

        die(Tools::jsonEncode($culqi_order->id));
        

    }
}