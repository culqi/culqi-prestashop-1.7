<?php

include_once dirname(__FILE__, 3) . '/libraries/culqi-php/lib/culqi.php';

class CulqiChargeAjaxModuleFrontController extends ModuleFrontController
{

    public function initContent()
    {
        parent::initContent();
        header('Content-Type: application/json');
        die(json_encode([
            'success' => true,
            'message' => 'ChargeAjax endpoint works!',
        ]));
        $this->ajax = true;
    }

    public function displayAjax()
    {
        $culqiPretashop = new Culqi();
        $infoCheckout = $culqiPretashop->getCulqiInfoCheckout();
        $amount_cart = $infoCheckout['total'];
        $currency_cart = $infoCheckout['currency'];
        $enviroment_cart = $infoCheckout['enviroment_backend'];
        $address = $infoCheckout['address'];
        $country = $infoCheckout['country'];
        $firstname = $infoCheckout['firstname'];
        $lastname = $infoCheckout['lastname'];
        $culqi = new Culqi\Culqi(array('api_key' => $infoCheckout['llave_secreta']));
        $phone = ($address[0]['phone']!='' and !is_null($address[0]['phone'])) ? $address[0]['phone'] : false;
        if(!$phone) {
            $phone = $address[0]['phone_mobile'] ?: '999999999';
        }
        try {
            $cart = $this->context->cart;
            $customer = new Customer($cart->id_customer);
            $antifraud_charges = array();
            if (isset($firstname) and !empty($firstname) and !is_null($firstname) and $firstname != '') {
                $antifraud_charges['first_name'] = $firstname;
            }
            if (isset($lastname) and !empty($lastname) and !is_null($lastname) and $lastname != '') {
                $antifraud_charges['last_name'] = $lastname;
            }
            if (isset($address[0]['address1']) and !empty($address[0]['address1']) and !is_null($address[0]['address1']) and $address[0]['address1'] != '') {
                $antifraud_charges['address'] = $address[0]['address1'];
            }
            if (isset($address[0]['city']) and !empty($address[0]['city']) and !is_null($address[0]['city']) and $address[0]['city'] != '') {
                $antifraud_charges['address_city'] = $address[0]['city'];
            }
            if (isset($country[0]['iso_code']) and !empty($country[0]['iso_code']) and !is_null($country[0]['iso_code']) and $country[0]['iso_code'] != '') {
                $antifraud_charges['country_code'] = $country[0]['iso_code'];
            }
            
            $antifraud_charges['phone_number'] = $phone;

            $antifraud_charges['device_finger_print_id'] = Tools::getValue("device");
            $args_charge = array(
                'amount' => (int)$amount_cart,
                'currency_code' => $currency_cart,
                'email' => Tools::getValue("email"),
                'source_id' => Tools::getValue("token_id"),
                'capture' => true,
                'enviroment' => $enviroment_cart,
                'antifraud_details' => $antifraud_charges,
                'metadata' => ["order_id" => (string)$cart->id, "sponsor" => "prestashop"],
            );
            if (Tools::getValue("parameters3DS") !== FALSE) {
                $args_charge['authentication_3DS'] = Tools::getValue("parameters3DS");
            }
            $culqi_charge = $culqi->Charges->create($args_charge);
        } catch (Exception $e) {
            die(json_encode($e->getMessage()));
        }
        die(json_encode($culqi_charge));
    }

}
