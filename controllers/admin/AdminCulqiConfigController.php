<?php

include_once dirname(__FILE__, 3) . '/culqi.php';

class AdminCulqiConfigController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view'; // o 'edit' según corresponda
        $this->context = Context::getContext();
        $this->module = Module::getInstanceByName('culqi'); // Instancia del módulo
        parent::__construct();
    }

    public function initContent() {
        parent::initContent();
        $this->context->smarty->assign(array(
            'my_variable' => 'Hello, Culqi!',
        ));
        $this->setTemplate('configure.tpl');
    }

    public function postProcess()
    {
        if (!Context::getContext()->employee || !Context::getContext()->employee->isLoggedBack()) {
            die(json_encode(['success' => false, 'message' => 'Access denied.']));
        }
        if (Tools::getValue('action') === 'saveConfig') {
            try {
                if (!isset($this->context->employee) || !$this->context->employee->isLoggedBack()) {
                    throw new Exception('Access denied: Admin only.');
                }
                $pk = Tools::getValue("publicKey");
                $status = Tools::getValue("pluginStatus");
                if (Configuration::get('CULQI_ENABLED') == '') {
                    $status = 'true';
                }
                $merchant = Tools::getValue("merchant");
                $rsa_pk = Tools::getValue("rsa_pk");
                $rsa_sk_plugin = Tools::getValue("rsa_plugin_sk");
                $payment_methods = Tools::getValue("payment_methods");

                Configuration::updateValue('CULQI_ENABLED', $status) &&
                Configuration::updateValue('CULQI_LLAVE_PUBLICA', $pk) &&
                Configuration::updateValue('CULQI_PAYMENT_TYPES', $payment_methods) &&
                Configuration::updateValue('CULQI_MERCHANT', $merchant) &&
                Configuration::updateValue('CULQI_RSA_PK', $rsa_pk) &&
                Configuration::updateValue('CULQI_RSA_PLUGIN_SK',$rsa_sk_plugin);
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
