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
            $headers = getallheaders();
            $data = json_decode($rawData, true);
            $this->postProcessWebhooks($headers, $data);
            /*$cart_id = $data["orderId"];
            $customer_secure_key = $data["orderKey"];
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
                'order_id' => $this->module->currentOrder,
                'data' => $success_url,
            ]));*/
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

    private function postProcessWebhooks($headers, $data)
    {
        Logger::addLog('Inicio webhook');
/*
        $headers = $headers['Authorization'];
        if(!isset($headers)){
        	exit("Error: Cabecera Authorization no presente");
        }*/
        /*
        $authorization = substr($headers,6);
        $credenciales = base64_decode($authorization);
        $credenciales = explode( ':', $credenciales );
        $username = $credenciales[0];
        $password = $credenciales[1];
        if(!isset($username) or !isset($password)){
        	exit("Error: No autorizado");
        }
        */

        Logger::addLog('$data ' . serialize($data));
        $order_id = (int)trim($data['orderId']);
        $status = trim($data['status']);	
        $transaction_id = trim($data['transactionId']);

        Logger::addLog('Charge -> se cambio el estado a: '.$status);    

        switch (get_payment_type($transaction_id)) {

            case 'charge':
                if ($status == "refunded"){

                    $state_refund = 7;
    
                    $this->updateOrderAndcreateOrderHistoryState($order_id, $state_refund);
                }
                break;

            case 'order':
                $state = 'CULQI_STATE_OK';
                if ($status === "expired") {
                    $state = 'CULQI_STATE_EXPIRED';
                }
                if ($stateRequest != 'pending') {
                    $this->updateOrderAndcreateOrderHistoryState($order_id, Configuration::get($state));
                }
                break;
        }
        echo json_encode(['success' => 'true', 'msj' => 'Operación exitosa']);
    }

    private function updateOrderAndcreateOrderHistoryState($id_order, $id_state)
    {
        $new_history = new OrderHistory();
        $new_history->id_order = (int)$id_order;
        $new_history->id_order_state = (int)$id_state;
        $new_history->add(true);
        $new_history->save();
        $order = new Order($id_order);
        $order->current_state = (int)$id_state;
        $order->update();
    }

    public function get_payment_type($id) {
        $type = (substr( $id, 0, 4 ) === "ord_") ? "order" : "charge";
        return $type;
    }
}