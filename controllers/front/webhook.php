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
        /* ini_set('display_startup_errors', 1);
        ini_set('display_errors', 1);
        error_reporting(-1); */

        Logger::addLog('Inicio weebhook');

        $postBody = file_get_contents("php://input");
        
        $postBody = json_decode($postBody, true);
        //Logger::addLog('$postBody' . serialize($postBody));
        $data = json_decode($postBody["data"], true);
        Logger::addLog('$data ' . serialize($data));
        //Logger::addLog('$$postBody->object ' . $postBody["object"]);


        if ($postBody["object"] == 'event' && $postBody["type"] == 'order.status.changed') {
            
            Logger::addLog('$entro if');
            Logger::addLog('$metadata' . serialize($data["metadata"]));
            
            $metadata = $data["metadata"];
            Logger::addLog('$metadata2' . serialize($metadata));

            $order_id = (int)$metadata["pts_order_id"];
            Logger::addLog('$order_id ' . $order_id);
            //$order_payment = Db::getInstance()->ExecuteS("SELECT * FROM " . _DB_PREFIX_ . "order_payment where transaction_id='" . $order_id . "'");
        
            //$order_reference = $order_payment[0]['order_reference'];

            $findorder = Db::getInstance()->ExecuteS("SELECT * FROM " . _DB_PREFIX_ . "orders where id_cart='" . $order_id . "'");

            $id = $findorder[0]['id_order'];
            Logger::addLog('$id ' . $id);

            $state = 'CULQI_STATE_OK';
            $stateRequest = $data["state"];
            Logger::addLog('$state ' . $stateRequest);
            if($stateRequest=='expired'){
                $state = 'CULQI_STATE_OK';
            }
            if($stateRequest!='pending'){
                $order = new Order($id);
                $order->current_state = (int)Configuration::get($state);
                $order->update();
            }
        }


        
        
        //var_dump('Actualizado!');
        exit(1);
    }
}