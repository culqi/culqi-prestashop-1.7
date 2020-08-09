<?php

class CulqiValidationModuleFrontController extends ModuleFrontController
{

    public function initContent()
    {
        parent::initContent();

        $type = Tools::getValue('clqtype');
        $code = Tools::getValue('clqcode');
        $cart = $this->context->cart;
        $customer = new Customer($cart->id_customer);

        switch ($type) {
            case 'charge':
                $ps_os_payment = $code == "AUT0000" ? Configuration::get('CULQI_STATE_OK') : Configuration::get('CULQI_STATE_ERROR');
                break;

            case 'order':
                $ps_os_payment = $code != "" ? Configuration::get('CULQI_STATE_PENDING') : Configuration::get('CULQI_STATE_ERROR');
                break;

            default:
                $ps_os_payment = Configuration::get('CULQI_STATE_ERROR');
        }

        $this->module->validateOrder((int)$cart->id, $ps_os_payment, (float)$cart->getordertotal(true), 'Culqi', null, array(), (int)$cart->id_currency, false, $customer->secure_key);

        Tools::redirect('index.php?controller=order-confirmation&id_cart=' . (int)$cart->id . '&id_module=' . (int)$this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key);
    }

}
