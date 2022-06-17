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
        //var_dump($infoCheckout); exit(1);
        $culqi = new Culqi\Culqi(array('api_key' => $infoCheckout['llave_secreta'] ));

        $args_order = array(
             
            'amount' => (int)$infoCheckout['total'],
            'currency_code' => $infoCheckout['currency'],
            'description' => $infoCheckout['descripcion'],
            'order_number' => (string)$infoCheckout['orden'] . time() + 24 * 60 * 60,
            'client_details' => array(
                'email' => $infoCheckout['customer']->email,
                'first_name' => $infoCheckout['customer']->firstname,
                'last_name' => $infoCheckout['customer']->lastname,
                'phone_number' => $infoCheckout['address'][0]['phone']
            ),
            'expiration_date' => time() + 24 * 60 * 60,
            'confirm' => false,
            'enviroment' => $infoCheckout['enviroment_backend']

        );
        
        $culqi_order = $culqi->Orders->create( $args_order );
        //echo var_dump($culqi_order);

        die(Tools::jsonEncode($culqi_order->id));
        

    }
}