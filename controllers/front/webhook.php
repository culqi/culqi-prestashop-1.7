<?php

class CulqiWebHookModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        $this->ajax = true;
    }

    public function displayAjax()
    {
        ini_set('display_startup_errors', 1);
        ini_set('display_errors', 1);
        error_reporting(-1);



        $postBody = file_get_contents("php://input");
        $postBody = json_decode($postBody, true);
        $order_id = $postBody['data']['id'];
        $order_payment = Db::getInstance()->ExecuteS("SELECT * FROM " . _DB_PREFIX_ . "order_payment where transaction_id='" . $order_id . "'");

        $order_reference = $order_payment[0]['order_reference'];

        $findorder = Db::getInstance()->ExecuteS("SELECT * FROM " . _DB_PREFIX_ . "orders where reference='" . $order_reference . "'");

        $id = $findorder[0]['id_order'];

        $state = 'CULQI_STATE_OK';
        $stateRequest = $postBody['data']['state'];
        if($stateRequest=='expired'){
            $state = 'CULQI_STATE_OK';
        }
        if($stateRequest!='pending'){
            $order = new Order($id);
            $order->current_state = (int)Configuration::get($state);
            $order->update();
        }



        var_dump('Actualizado!');
        exit(1);
    }
}