<?php

class CulqiPostpaymentpendingModuleFrontController extends ModuleFrontController
{	

    public function initContent()
    {
        parent::initContent();
        $this->ajax = false;
        //var_dump($this->context); exit(1);
        
        $extra_variables = array();
        $extra_variables['transaction_id'] = Tools::getValue("transaction_id");
        
        $cart = $this->context->cart;
        $customer = new Customer($cart->id_customer);
        $this->module->validateOrder((int)$cart->id, Configuration::get('CULQI_STATE_PENDING'), (float)$cart->getordertotal(true), 'Culqi', null, array(), (int)$cart->id_currency, false, $customer->secure_key);

        //Actualizando el transaction id
        $id_order = Order::getOrderByCartId($this->context->cart->id);

        $order = new Order($id_order);
        $order_payment_collection = $order->getOrderPaymentCollection();

        $order_payment = $order_payment_collection[0];

        $order_payment->transaction_id = Tools::getValue("transaction_id");
        $order_payment->update();
       
        Tools::redirect('index.php?controller=order-confirmation&id_cart=' . (int)$cart->id . '&id_module=' . (int)$this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key);

    }

}