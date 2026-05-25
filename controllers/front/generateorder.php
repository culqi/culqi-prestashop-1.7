<?php

include_once dirname(__FILE__, 3) . '/culqi.php';

class CulqiGenerateOrderModuleFrontController extends ModuleFrontController
{
    private $logger;

    public function __construct()
    {
        parent::__construct();
        $this->logger = CulqiLogger::get_instance();
    }

    public function initContent()
    {
        parent::initContent();
        $this->ajax = false;
    }
    
    public function postProcess()
    {
        $this->logger->info('Webhook', '[generateorder] Webhook received');

        header('Content-Type: application/json');
        try {
            $shop_domain = Tools::getShopDomainSsl(true);
            $rawData = file_get_contents('php://input');
            $headers = getallheaders();
            $headers = $headers['Authorization'];

            if (!isset($headers)) {
                $this->logger->warning('Order', '[generateorder] Authorization header missing');
                exit("Error: Cabecera Authorization no presente");
            }

            $this->logger->info('Order', '[generateorder] Generate order process started');

            $token = explode(' ', $headers)[1];
            $is_verified = verify_jwt_token($token);

            if (!$is_verified) {
                $this->logger->warning('Order', '[generateorder] Token verification failed');
                http_response_code(401);
                die(json_encode([
                    'type' => 'error',
                    'order_id' => 0,
                    'user_message' => 'Token no verificado',
                ]));
            }

            $this->logger->debug('Order', '[generateorder] Token verified successfully');

            $data = json_decode($rawData, true);
            $cart_id = $data["orderId"];
            $customer_secure_key = $data["orderKey"];
            $card_number = $data["cardNumber"] ?? '';
            $card_brand = $data["cardBrand"] ?? '';
            $transaction_id = $data["transactionId"] ?? '';

            $this->logger->info('Order', '[generateorder] Processing order generation', [
                'cart_id' => $cart_id,
                'transaction_id' => $transaction_id,
            ]);

            $id_order = Order::getIdByCartId($cart_id);
            if ($id_order) {
                $this->logger->info('Order', '[generateorder] Order already exists, skipping creation', [
                    'cart_id' => $cart_id,
                    'order_id' => $id_order,
                ]);
                $order = new Order($id_order);
                $success_url = Context::getContext()->link->getPageLink(
                    'order-confirmation',
                    null,
                    null,
                    [
                        'id_cart' => (int)$cart_id,
                        'id_module' => (int)$this->module->id,
                        'id_order' => $order->id,
                        'key' => $customer_secure_key,
                    ]
                );
                die(json_encode([
                    'success' => true,
                    'order_id' => $order->id,
                    'data' => $success_url,
                    'message' => 'Order already exists'
                ]));
            } else {
                $culqi_status = $this->getCulqiStatus($transaction_id);
                $cart = new Cart($cart_id);

                $this->logger->info('Order', '[generateorder] Creating new order', [
                    'cart_id' => $cart_id,
                    'culqi_status' => $culqi_status,
                    'cart_total' => $cart->getordertotal(true),
                ]);

                $this->module->validateOrder((int)$cart_id, $culqi_status, (float)$cart->getordertotal(true), 'Culqi', null, array(), (int)$cart->id_currency, false, $customer_secure_key);
                $id_order = Order::getIdByCartId($cart_id);
                $order = new Order($id_order);
                $order_payment_collection = $order->getOrderPaymentCollection();
                $order_payment = $order_payment_collection[0];
                $order_payment->card_number = $card_number;
                $order_payment->card_brand = $card_brand;
                $order_payment->transaction_id = $transaction_id;
                $order_payment->update();

                $this->logger->info('Order', '[generateorder] Order created successfully', [
                    'order_id' => $this->module->currentOrder,
                    'cart_id' => $cart_id,
                    'transaction_id' => $transaction_id,
                ]);

                $success_url = Context::getContext()->link->getPageLink(
                    'order-confirmation',
                    null,
                    null,
                    [
                        'id_cart' => (int)$cart_id,
                        'id_module' => (int)$this->module->id,
                        'id_order' => $this->module->currentOrder,
                        'key' => $customer_secure_key,
                    ]
                );
                die(json_encode([
                    'success' => true,
                    'order_id' => $this->module->currentOrder,
                    'data' => $success_url,
                ]));
            }
        } catch (Exception $e) {
            $this->logger->error('Order', '[generateorder] Order generation failed', [
                'error' => $e->getMessage(),
            ]);
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
/*
    private function postProcessWebhooks($headers, $data)
    {
        Logger::addLog('Inicio webhook');*/
/*
        $headers = $headers['Authorization'];
        if(!isset($headers)){
        	exit("Error: Cabecera Authorization no presente");
        }*/
/*
        Logger::addLog('$data ' . serialize($data));
        $order_id = (int)trim($data['orderId']);
        $status = trim($data['status']);	
        $transaction_id = trim($data['transactionId']);

        Logger::addLog('Charge -> se cambio el estado a: '.$status);    
        try {
            switch ($this->get_payment_type($transaction_id)) {

                case 'charge':
                    if ($status == "refunded"){
                        //$state_refund = 7;
                         $state = 'CULQI_STATE_REFUND';
                        //$this->updateOrderAndcreateOrderHistoryState($order_id, $state_refund);
                    }
                    break;

                case 'order':
                    $state = 'CULQI_STATE_OK';
                    if ($status === "cancelled") {//expirado
                        $state = 'CULQI_STATE_EXPIRED';
                    }
                    break;
            }
            $this->updateOrderAndcreateOrderHistoryState($order_id, Configuration::get($state));

            http_response_code(201);
            echo json_encode(['type' => 'success', 'user_message' => 'Operación exitosa']);
        } catch (Exception $e) {
            http_response_code(400);
            Logger::addLog('Error -> '.$e->getMessage());    
            die(json_encode([
                'success' => false,
                'data' => $e->getMessage(),
            ]));
            echo json_encode(['type' => 'eror', 'user_message' => 'Erro al ejecutar el webhook']);
        }
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
    }*/
}