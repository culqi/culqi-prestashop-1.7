<?php

include_once dirname(__FILE__, 3) . '/culqi.php';

class CulqiGenerateOrderModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        $this->ajax = false;
    }
    
    public function postProcess()
    {
        header('Content-Type: application/json');
        try {
            $shop_domain = Tools::getShopDomainSsl(true);
            $rawData = file_get_contents('php://input');
            $data = json_decode($rawData, true);
            $cart_id = $data["cartId"];
            $customer_secure_key = $data["customerSecureKey"];
            $card_number = $data["cardNumber"] ?? '';
            $card_brand = $data["cardBrand"] ?? '';
            $transaction_id = $data["transactionId"] ?? '';
            $culqi_status = $this->getCulqiStatus($transaction_id);
            $cart = new Cart($cart_id);
            $this->module->validateOrder((int)$cart_id, $culqi_status, (float)$cart->getordertotal(true), 'Culqi', null, array(), (int)$cart->id_currency, false, $customer_secure_key);
            $id_order = Order::getIdByCartId($cart_id);
            $order = new Order($id_order);
            $order_payment_collection = $order->getOrderPaymentCollection();

            $order_payment = $order_payment_collection[0];
            $order_payment->card_number = $card_number;
            $order_payment->card_brand = $card_brand;
            $order_payment->transaction_id = $transaction_id;
            $order_payment->update();
            $success_url = $shop_domain . '/index.php?controller=order-confirmation&id_cart=' . (int)$cart_id . '&id_module=' . (int)$this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer_secure_key;
            die(json_encode([
                'success' => true,
                'data' => $success_url,
            ]));
        } catch (Exception $e) {
            die(json_encode([
                'success' => false,
                'data' => $e->getMessage(),
            ]));
        }
    }

    private function getCulqiStatus($transaction_id)
    {
        $culqi_status = Configuration::get('CULQI_STATE_ERROR');
        if (substr($transaction_id, 0, 4) === 'ord_') {
            $culqi_status = Configuration::get('CULQI_STATE_PENDING');
        } elseif (substr($transaction_id, 0, 4) === 'chr_') {
            $culqi_status = Configuration::get('CULQI_STATE_OK');
        }

        return $culqi_status;
    }
}