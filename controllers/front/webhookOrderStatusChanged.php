<?php

class CulqiWebhookOrderStatusChangedModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $this->ajax = true;

        // $input = '{"object":"event","id":"evt_live_Tf1WJVWcSHF9R3HA","type":"order.status.changed","creation_date":1596248428134,"data":"{\"object\":\"order\",\"id\":\"ord_live_BfjQayoI9DhEwUBG\",\"amount\":1320,\"payment_code\":\"36338442\",\"currency_code\":\"PEN\",\"description\":\"Orden de compra 416\",\"order_number\":\"#id-2570\",\"state\":\"paid\",\"total_fee\":null,\"net_amount\":null,\"fee_details\":null,\"creation_date\":1596247975,\"expiration_date\":1596334374,\"updated_at\":null,\"paid_at\":null,\"available_on\":null,\"metadata\":{}}"}';
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
                $customer = new Customer($cart->id_customer);

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
                $ps_os_payment = $data['state'] == "paid" ? Configuration::get('CULQI_STATE_OK') : Configuration::get('CULQI_STATE_ERROR');
                $module_name = $this->module->displayName;



                // $this->module->validateOrder((int)$cart->id, $ps_os_payment, (float)$cart->getordertotal(true), $module_name, null, null, (int)$cart->id_currency, false, $customer->secure_key);

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
