<?php

class CulqiUpdateOrderWithWebHookModuleFrontController extends ModuleFrontController
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
        $this->ajax = true;
    }

    public function displayAjax()
    {
        $this->logger->info('Webhook', '[updateorderwithwebhook] Webhook received');

        header('Content-Type: application/json');
        $shop_domain = Tools::getShopDomainSsl(true);
        $rawData = file_get_contents('php://input');
        $headers = getallheaders();
        $data = json_decode($rawData, true);

        $this->logger->debug('Webhook', '[updateorderwithwebhook] Payload received', [
            'shop_domain' => $shop_domain,
            'has_data' => !empty($data),
        ]);

        $headers = $headers['Authorization'];
        if (!isset($headers)) {
            $this->logger->warning('Webhook', '[updateorderwithwebhook] Authorization header missing');
            exit("Error: Cabecera Authorization no presente");
        }

        $token = explode(' ', $headers)[1];
        $is_verified = verify_jwt_token($token);

        if (!$is_verified) {
            $this->logger->warning('Webhook', '[updateorderwithwebhook] Token verification failed');
            http_response_code(401);
            die(json_encode([
                'type' => 'error',
                'order_id' => 0,
                'user_message' => 'Token no verificado',
            ]));
        }

        $this->logger->debug('Webhook', '[updateorderwithwebhook] Token verified successfully');

        $order_id = (int)trim($data['orderId']);
        $status = trim($data['status']);
        $transaction_id = trim($data['transactionId']);

        $this->logger->info('Webhook', '[updateorderwithwebhook] Processing webhook', [
            'order_id' => $order_id,
            'status' => $status,
            'transaction_id' => $transaction_id,
            'payment_type' => $this->get_payment_type($transaction_id),
        ]);

        $state = null;
        try {
            switch ($this->get_payment_type($transaction_id)) {

                case 'charge':
                    if ($status == "refunded") {
                        $state = 'CULQI_STATE_REFUND';
                    }
                    break;

                case 'order':
                    if ($status === "processing") {
                        $state = 'CULQI_STATE_OK';
                    }
                    if ($status === "cancelled") {
                        $state = 'CULQI_STATE_EXPIRED';
                    }
                    break;
            }

            if ($state !== null) {
                $this->updateOrderAndcreateOrderHistoryState($order_id, Configuration::get($state));
                $this->logger->info('Webhook', '[updateorderwithwebhook] Order state updated', [
                    'order_id' => $order_id,
                    'new_state' => $state,
                ]);
            }

            http_response_code(201);
            die(json_encode([
                'type' => 'success',
                'order_id' => $order_id,
                'user_message' => 'Operación exitosa',
            ]));
        } catch (Exception $e) {
            $this->logger->error('Webhook', '[updateorderwithwebhook] Webhook processing failed', [
                'error' => $e->getMessage(),
                'order_id' => $order_id,
            ]);
            http_response_code(400);
            die(json_encode([
                'type' => 'error',
                'order_id' => $order_id,
                'user_message' => 'Error al ejecutar el webhook, ' . $e->getMessage(),
            ]));
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
    }
}
