<?php

class CulqiUpdateOrderWithWebHookModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        $this->ajax = true;
    }

    public function displayAjax()
    {
        Logger::addLog('Inicio weebhook');
        header('Content-Type: application/json');
        $shop_domain = Tools::getShopDomainSsl(true);
        $rawData = file_get_contents('php://input');
        $headers = getallheaders();
        $data = json_decode($rawData, true);

        $headers = $headers['Authorization'];
        if(!isset($headers)){
        	exit("Error: Cabecera Authorization no presente");
        }

        $token = explode(' ', $headers)[1];
        $is_verified = verify_jwt_token($token);
        if(!$is_verified){
            Logger::addLog('Error: Token no verificado');
            http_response_code(401);
            die(json_encode([
                'type' => 'error',
                'order_id' => 0,
                'user_message' => 'Token no verificado',
            ]));
        }
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
                    if ($status === "processing") {//pagado
                        $state = 'CULQI_STATE_OK';
                    }
                    if ($status === "cancelled") {//expirado
                        $state = 'CULQI_STATE_EXPIRED';
                    }
                    /*if ($status != 'pending') {
                        $this->updateOrderAndcreateOrderHistoryState($order_id, Configuration::get($state));
                    }*/
                    break;
            }
            $this->updateOrderAndcreateOrderHistoryState($order_id, Configuration::get($state));
            
            http_response_code(201);
            die(json_encode([
                'type' => 'success',
                'order_id' => $order_id,
                'user_message' => 'Operación exitosa',
            ]));
        } catch (Exception $e) {
            http_response_code(400);
            Logger::addLog('Error -> '.$e->getMessage());    
            die(json_encode([
                'type' => 'error',
                'order_id' => $order_id,
                'user_message' => 'Error al ejecutar el webhook, '.$e->getMessage(),
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
