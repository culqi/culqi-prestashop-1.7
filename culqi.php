<?php

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_'))
    exit;

define( 'CULQI_API_URL' , 'https://ag-shopify.culqi.com/gateway/' );
define( 'CULQI_CONFIG_URL' , 'https://configonlineplatform.culqi.com' );
define( 'EXPIRATION_TIME' , 15 );
define('CULQI_PLUGIN_VERSION', '4.0.0');
define('LOADER_IMG', 'https://icon-library.com/images/loading-icon-transparent-background/loading-icon-transparent-background-12.jpg');

#[AllowDynamicProperties]
class Culqi extends PaymentModule
{

    private $_postErrors = array();

    public function __construct()
    {
        $this->name = 'culqi';
        $this->tab = 'payments_gateways';
        $this->version = CULQI_PLUGIN_VERSION;
        $this->controllers = array('chargeajax', 'postpayment', 'generateorder', 'webhook', 'registersale', 'config');
        $this->author = 'Culqi';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        $this->display = 'view';

        parent::__construct();

        $this->meta_title = 'Culqi';
        $this->displayName = 'Culqi';
        $this->description = $this->l('Conéctate a nuestra pasarela de pagos para aumentar tus ventas.');
        $this->confirmUninstall = $this->l('¿Estás seguro que quieres desintalar el módulo de Culqi?');

    }

    public function install()
    {
        //$this->createStates();
        $this->clearCache();

        return (
            parent::install() &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('paymentOptions') &&
            Configuration::updateValue('CULQI_ENABLED', '') &&
            Configuration::updateValue('CULQI_LLAVE_PUBLICA', '') &&
            Configuration::updateValue('CULQI_PAYMENT_TYPES', '') &&
            Configuration::updateValue('CULQI_MERCHANT', '') &&
            Configuration::updateValue('CULQI_RSA_PK', '')
        );
    }

    public function hookDisplayHeader()
    {
        if (Tools::getValue('controller') === 'order') {
            Media::addJsDef(array(
                'modulePath' => $this->_path,
                'loaderImg' => LOADER_IMG
            ));

            $register_sale_url = $this->context->link->getModuleLink('culqi', 'registersale', []);

            $jsCode = "<script>
                var register_sale_url = '{$register_sale_url}';
            </script>";

            $this->context->controller->registerJavascript(
                'culqifunctions',
                $this->_path.'views/js/culqi.js?_='.time(),
                array('server' => 'remote', 'position' => 'bottom', 'priority' => 10000)
            );

            return $jsCode;
        }
    }

    private function clearCache()
    {
        Tools::clearSmartyCache();
		Tools::clearXMLCache();
		Tools::clearCache();
		Tools::generateIndex();
    }

    public function errorPayment($mensaje)
    {
        $smarty = $this->context->smarty;
        $smarty->assign('culqi_error_pago', $mensaje);
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
        $newOption = new PaymentOption();
        if ($this->getConfigFieldsValues()['status'] == 'true') {
            $newOption->setModuleName($this->name)
                ->setCallToActionText($this->trans('Culqi', array(), 'culqi'))
                ->setLogo($this->_path.'/culqi-logo.svg')
                ->setAction($this->context->link->getModuleLink($this->name, 'postpayment', array(), true))
                ->setAdditionalInformation($this->context->smarty->fetch('module:culqi/views/templates/hook/paymentCulqiView.tpl'));
            $payment_options = [
                $newOption,
            ];
            return $payment_options;
        } else {
            return false;
        }
        return false;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency((int)($cart->id_currency));
        $currencies_module = $this->getCurrency((int)$cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    public function loadCheckoutView($is_header = false) {
        return [
            "module_dir" => "culqi"
        ];
    }

    public function uninstallStates()
    {
        if (Db::getInstance()->Execute("DELETE FROM " . _DB_PREFIX_ . "order_state WHERE id_order_state = ( SELECT value
                FROM " . _DB_PREFIX_ . "configuration WHERE name =  'CULQI_STATE_OK' )") &&
            Db::getInstance()->Execute("DELETE FROM " . _DB_PREFIX_ . "order_state_lang WHERE id_order_state = ( SELECT value
                FROM " . _DB_PREFIX_ . "configuration WHERE name =  'CULQI_STATE_OK' )") &&
            Db::getInstance()->Execute("DELETE FROM " . _DB_PREFIX_ . "order_state WHERE id_order_state = ( SELECT value
                FROM " . _DB_PREFIX_ . "configuration WHERE name =  'CULQI_STATE_PENDING' )") &&
            Db::getInstance()->Execute("DELETE FROM " . _DB_PREFIX_ . "order_state_lang WHERE id_order_state = ( SELECT value
                FROM " . _DB_PREFIX_ . "configuration WHERE name =  'CULQI_STATE_PENDING' )") &&
            Db::getInstance()->Execute("DELETE FROM " . _DB_PREFIX_ . "order_state WHERE id_order_state = ( SELECT value
                FROM " . _DB_PREFIX_ . "configuration WHERE name =  'CULQI_STATE_ERROR' )") &&
            Db::getInstance()->Execute("DELETE FROM " . _DB_PREFIX_ . "order_state_lang WHERE id_order_state = ( SELECT value
                FROM " . _DB_PREFIX_ . "configuration WHERE name =  'CULQI_STATE_ERROR' )") &&
            Db::getInstance()->Execute("DELETE FROM " . _DB_PREFIX_ . "order_state WHERE id_order_state = ( SELECT value
                FROM " . _DB_PREFIX_ . "configuration WHERE name =  'CULQI_STATE_EXPIRED' )") &&
            Db::getInstance()->Execute("DELETE FROM " . _DB_PREFIX_ . "order_state_lang WHERE id_order_state = ( SELECT value
                FROM " . _DB_PREFIX_ . "configuration WHERE name =  'CULQI_STATE_EXPIRED' )")
        ) return true;
        return false;
    }

    public function uninstall()
    {
        if (!parent::uninstall()
            || !Configuration::deleteByName('CULQI_STATE_OK')
            || !Configuration::deleteByName('CULQI_STATE_PENDING')
            || !Configuration::deleteByName('CULQI_STATE_ERROR')
            || !Configuration::deleteByName('CULQI_STATE_EXPIRED')
            || !Configuration::deleteByName('CULQI_ENABLED')
            || !Configuration::deleteByName('CULQI_LLAVE_PUBLICA')
            || !Configuration::deleteByName('CULQI_PAYMENT_TYPES')
            || !Configuration::deleteByName('CULQI_MERCHANT')
            || !Configuration::deleteByName('CULQI_RSA_PK')
            || !$this->uninstallStates())
            return false;
        return true;
    }

    private function _displayInfo()
    {
        return $this->display(__FILE__, 'info.tpl');
    }

    public function getContent()
    {

        $this->_html = '';
        $this->_html .= $this->renderForm();

        return $this->_html;
    }

    /**
     * Admin Zone
     */

    public function renderForm()
    {
        $this->context->smarty->assign(array(
            'currentIndex' => $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name,
            'token' => Tools::getAdminTokenLite('AdminModules'),
            'fields_value' => $this->getConfigFieldsValues(),
            'culqi_config_url' => CULQI_CONFIG_URL,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
            'save_config_ajax_url' => $this->context->link->getModuleLink('culqi', 'config', []),
        ));

        return $this->display(__FILE__, '/views/templates/hook/setting.tpl');
    }

    public function getConfigFieldsValues()
    {
        
        $status = Configuration::get('CULQI_ENABLED') ?? '';
        $pk = Configuration::get('CULQI_LLAVE_PUBLICA') ?? '';
        $merchant = Configuration::get('CULQI_MERCHANT') ?? '';
        $payment_methods = Configuration::get('CULQI_PAYMENT_TYPES') ?? '';

        return [
            'status' => (bool) ($status === 'true'),
            'pk' => $pk,
            'merchant' => $merchant,
            'payment_methods' => $payment_methods,
            'shop_url' => Context::getContext()->shop->getBaseURL()
        ];
    }

    private function _postProcess(){}

    public function hookActionOrderStatusPostUpdate($params)
    {
        $this->context->cart->save();  // Save the cart to avoid clearing
    }

}


class CulqiPago
{
    public static $llaveSecreta;
    public static $codigoComercio;
}
