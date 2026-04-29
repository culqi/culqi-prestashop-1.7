<?php

include_once dirname(__FILE__, 3) . '/culqi.php';

class AdminCulqiConfigController extends ModuleAdminController
{
    private $logger;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view'; // o 'edit' según corresponda
        $this->context = Context::getContext();
        $this->module = Module::getInstanceByName('culqi'); // Instancia del módulo
        $this->logger = CulqiLogger::get_instance();
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
        if (Tools::getValue('action') === 'checkSession') {
            $this->logger->debug('Config', 'Session check requested');
            die(json_encode([
                'session_valid' => $this->context->employee->isLoggedBack(),
            ]));
        }

        if (!Context::getContext()->employee || !Context::getContext()->employee->isLoggedBack()) {
            $this->logger->warning('Config', 'Access denied - not logged in');
            die(json_encode(['success' => false, 'message' => 'Access denied.']));
        }

        if (Tools::getValue('action') === 'saveConfig') {
            $this->logger->info('Config', 'Config save initiated');

            try {
                if (!isset($this->context->employee) || !$this->context->employee->isLoggedBack()) {
                    throw new Exception('Access denied: Admin only.');
                }
                $pk = Tools::getValue("publicKey");
                $status = Tools::getValue("pluginStatus");
                $merchant = Tools::getValue("merchant");
                $rsa_pk = Tools::getValue("rsa_pk");
                $rsa_sk_plugin = Tools::getValue("rsa_plugin_sk");
                $payment_methods = Tools::getValue("payment_methods");

                $update_data = [];
                if (Configuration::get('CULQI_ENABLED') == '') {
                    $status = 'true';
                }

                if (!empty($status)) {
                    Configuration::updateValue('CULQI_ENABLED', $status);
                    $update_data['enabled'] = $status;
                }
                if (!empty($pk)) {
                    Configuration::updateValue('CULQI_LLAVE_PUBLICA', $pk);
                    $update_data['public_key'] = '(set)';
                }
                if (!empty($payment_methods)) {
                    Configuration::updateValue('CULQI_PAYMENT_TYPES', $payment_methods);
                    $update_data['payment_methods'] = '(set)';
                }
                if (!empty($rsa_pk)) {
                    Configuration::updateValue('CULQI_RSA_PK', $rsa_pk);
                    $update_data['rsa_pk'] = '(set)';
                }
                if (!empty($rsa_sk_plugin)) {
                    Configuration::updateValue('CULQI_RSA_PLUGIN_SK', $rsa_sk_plugin);
                    $update_data['rsa_sk_plugin'] = '(set)';
                }

                $this->logger->info('Config', 'Configuration saved', ['fields_updated' => $update_data]);
            } catch (Exception $e) {
                $this->logger->error('Config', 'Config save failed', ['error' => $e->getMessage()]);
                die(json_encode($e->getMessage()));
            }

            die(json_encode([
                'success' => true,
                'message' => 'Configuration saved successfully.',
            ]));
        }

        if (Tools::getValue('action') === 'getNewConfigUrl') {
            if (!Context::getContext()->employee || !Context::getContext()->employee->isLoggedBack()) {
                $this->logger->warning('Config', 'Access denied for getNewConfigUrl');
                die(json_encode(['success' => false, 'message' => 'Access denied.']));
            }

            $this->logger->debug('Config', 'Generating new config URL');

            $module = Module::getInstanceByName('culqi');
            $newUrl = $module->getConfigUrl();

            $this->logger->info('Config', 'New config URL generated');

            die(json_encode([
                'success' => true,
                'url' => $newUrl,
            ]));
        }
    }

}
