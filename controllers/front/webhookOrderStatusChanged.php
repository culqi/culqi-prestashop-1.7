<?php

class CulqiWebhookOrderStatusChangedModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $this->ajax = true;
        
        $input = file_get_contents("php://input");

        if (Configuration::get('CULQI_WEBHOOK_CATCH_LOG')) {
            $date = new DateTime();
            $fp1 = fopen(dirname(__FILE__). '/../../logs/webhook.txt', 'w');
            $fp2 = fopen(dirname(__FILE__). '/../../logs/'. $date->format('Ymd_His') . '_webhook.txt', 'w');
            fwrite($fp1, $input);
            fwrite($fp2, $input);
            fclose($fp1);
            fclose($fp2);
        }

        if ($input === '') {
            $this->response(403, 'This url is only use to Webhooks from Culqi.');
        } else {
            $event_json = json_decode($input, true);

            if (isset($event_json['data'])) {
                $data = json_decode($event_json['data'], true);
                $idCart = substr($data['description'], 16);

                $cart = new Cart($idCart);

                switch ($data['state']) {
                    case 'paid':
                        $ps_os_payment = Configuration::get('CULQI_STATE_OK');
                        break;

                    case 'expired':
                        $ps_os_payment = Configuration::get('CULQI_STATE_EXPIRED');
                        break;

                    default:
                        $ps_os_payment = Configuration::get('CULQI_STATE_ERROR');
                }

                $orderObject = new Order();
                $order = new Order($orderObject->getOrderByCartId((int)$cart->id));

                $history = new OrderHistory();
                $history->id_order = (int)$order->id;
                $history->changeIdOrderState((int)$ps_os_payment, (int)($order->id));

                $this->response(200, 'Order updated successfully.');
            } else {
                $this->response(422, 'No contiene la estructura correcta.');
            }
        }
    }

    private function response($code, $message) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['message' => $this->trans($message, [], 'Modules.Culqi.Shop')]);
        die;
    }
}
