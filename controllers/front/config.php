<?php

include_once dirname(__FILE__, 3) . '/culqi.php';

class CulqiConfigModuleFrontController extends ModuleFrontController
{

    public function initContent()
    {
        parent::initContent();
        $this->ajax = true;
    }

    public function postProcess()
    {
        if (Tools::getValue('action') === 'saveConfig') {
            try {
                $pk = Tools::getValue("publicKey");
                $status = Tools::getValue("pluginStatus");
                if (Configuration::get('CULQI_ENABLED') == '') {
                    $status = 'true';
                }
                $merchant = Tools::getValue("merchant");
                $rsa_pk = Tools::getValue("rsa_pk");
                $payment_methods = Tools::getValue("payment_methods");

                Configuration::updateValue('CULQI_ENABLED', $status) &&
                Configuration::updateValue('CULQI_LLAVE_PUBLICA', $pk) &&
                Configuration::updateValue('CULQI_PAYMENT_TYPES', $payment_methods) &&
                Configuration::updateValue('CULQI_MERCHANT', $merchant) &&
                Configuration::updateValue('CULQI_RSA_PK',$rsa_pk);
            } catch (Exception $e) {
                die(json_encode($e->getMessage()));
            }

            die(json_encode([
                'success' => true,
                'message' => 'Configuration saved successfully.',
            ]));
        }
    }

}
