<?php

class CulqiOrderStatusChangedModuleFrontController extends ModuleFrontController
{   
    public function postProcess()
    {
        $this->ajax = true;

        /*
        $input = file_get_contents("php://input");

        $date = new DateTime();
        $fp = fopen(dirname(__FILE__) . DIRECTORY_SEPARATOR . $date->format('Ymd_His') . '_webhook.txt', 'w');
        fwrite($fp, $input);
        fclose($fp);

        http_response_code(200);
        echo json_encode($event_json);
        die;
        */

        $input = '{"object":"event","id":"evt_live_Tf1WJVWcSHF9R3HA","type":"order.status.changed","creation_date":1596248428134,"data":"{\"object\":\"order\",\"id\":\"ord_live_BfjQayoI9DhEwUBG\",\"amount\":1320,\"payment_code\":\"36338442\",\"currency_code\":\"PEN\",\"description\":\"Orden de compra 416\",\"order_number\":\"#id-2570\",\"state\":\"paid\",\"total_fee\":null,\"net_amount\":null,\"fee_details\":null,\"creation_date\":1596247975,\"expiration_date\":1596334374,\"updated_at\":null,\"paid_at\":null,\"available_on\":null,\"metadata\":{}}"}';
        $event_json = json_decode($input, true);

        $data = json_decode($event_json['data'], true);
        print_r($data); die;

        $ps_os_payment = $data['state'] == "paid" ? Configuration::get('CULQI_STATE_OK') : Configuration::get('CULQI_STATE_ERROR');

        $na = $this->module->displayName;
        $key = $customer->secure_key;
        
        $this->module->validateOrder($cart->id, $ps_os_payment, $total, $na, null, null, $currency->id, false, $key);
    }
}