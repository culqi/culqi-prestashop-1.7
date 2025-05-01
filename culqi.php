<?php

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_'))
    exit;

define( 'CULQI_API_URL' , 'https://ag-shopify-qa.culqi.xyz/gateway/' );
define( 'CULQI_CONFIG_URL' , 'https://configonlineplatform-qa.culqi.xyz/' );
define( 'EXPIRATION_TIME' , 15 );
define('CULQI_PLUGIN_VERSION', '4.0.0');
define('LOADER_IMG', 'https://icon-library.com/images/loading-icon-transparent-background/loading-icon-transparent-background-12.jpg');
define('PLATFORM', 'prestashop');

function generate_token()
{
    $minutes = EXPIRATION_TIME;
    $expirationTimeInSeconds = $minutes * 60;
    $exp = time() + $expirationTimeInSeconds;

    $rsa_pk = Configuration::get('CULQI_RSA_PK') ?? '';
    $public_key = Configuration::get('CULQI_LLAVE_PUBLICA') ?? '';
    $data = [
        "pk" => $public_key,
        "exp" => $exp
    ];

    $encryptedData = encrypt_data_with_rsa(json_encode($data), $rsa_pk);
    
    return $encryptedData;
}

function encrypt_data_with_rsa(string $jsonData, string $publicKeyString): ?string {
    try {
        $publicKey = openssl_pkey_get_public($publicKeyString);
        if ($publicKey === false) {
            throw new Exception("Invalid public key: " . openssl_error_string());
        }

        $encrypted = '';
        $result = openssl_public_encrypt($jsonData, $encrypted, $publicKey, OPENSSL_PKCS1_OAEP_PADDING);

        // openssl_free_key($publicKey);

        if ($result === false) {
            throw new Exception("Encryption failed: " . openssl_error_string());
        }

        return base64_encode($encrypted);
    } catch (Exception $e) {
        error_log("RSA Encryption Error: " . $e->getMessage());
        return null;
    }
    }

#[AllowDynamicProperties]
class Culqi extends PaymentModule
{

    private $_postErrors = array();

    public function __construct()
    {
        $this->name = 'culqi';
        $this->tab = 'payments_gateways';
        $this->version = CULQI_PLUGIN_VERSION;
        $this->controllers = array('chargeajax', 'postpayment', 'generateorder', 'webhook', 'registersale');
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
        $this->createStates();
        $this->clearCache();

        return (
            parent::install() &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('paymentOptions') &&
            Configuration::updateValue('CULQI_ENABLED', '') &&
            Configuration::updateValue('CULQI_LLAVE_PUBLICA', '') &&
            Configuration::updateValue('CULQI_PAYMENT_TYPES', '') &&
            Configuration::updateValue('CULQI_MERCHANT', '') &&
            Configuration::updateValue('CULQI_RSA_PK', '') &&
            Configuration::updateValue('CULQI_RSA_PLUGIN_SK', '')
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
                FROM " . _DB_PREFIX_ . "configuration WHERE name =  'CULQI_STATE_REFUND' )") &&
            Db::getInstance()->Execute("DELETE FROM " . _DB_PREFIX_ . "order_state_lang WHERE id_order_state = ( SELECT value
                FROM " . _DB_PREFIX_ . "configuration WHERE name =  'CULQI_STATE_REFUND' )") &&
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
            || !Configuration::deleteByName('CULQI_STATE_REFUND')
            || !Configuration::deleteByName('CULQI_STATE_PENDING')
            || !Configuration::deleteByName('CULQI_STATE_ERROR')
            || !Configuration::deleteByName('CULQI_STATE_EXPIRED')
            || !Configuration::deleteByName('CULQI_ENABLED')
            || !Configuration::deleteByName('CULQI_LLAVE_PUBLICA')
            || !Configuration::deleteByName('CULQI_PAYMENT_TYPES')
            || !Configuration::deleteByName('CULQI_MERCHANT')
            || !Configuration::deleteByName('CULQI_RSA_PK')
            || !Configuration::deleteByName('CULQI_RSA_PLUGIN_SK')
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
        if (!isset($this->context->employee)) {
            throw new Exception('Employee object is not set.');
        }
        if (!$this->context->employee->isLoggedBack()) {
            throw new Exception('Employee is not logged in.');
        }

        $this->context->smarty->assign(array(
            'currentIndex' => $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name,
            'token' => Tools::getAdminTokenLite('AdminModules'),
            'iframe_token' => generate_token(),
            'fields_value' => $this->getConfigFieldsValues(),
            'culqi_config_url' => CULQI_CONFIG_URL,
            'platform' => PLATFORM,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
            'save_config_ajax_url' => $this->context->link->getAdminLink('AdminCulqiConfig'),
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
            'shop_url' => Tools::getShopDomainSsl(true)
        ];
    }

    private function _postProcess(){}

    private function queryGetStates($txt_state)
    {
        $query = "SELECT count(*) as filas FROM  " . _DB_PREFIX_ . "order_state a,  " . _DB_PREFIX_ . "order_state_lang b WHERE b.id_order_state = a.id_order_state AND a.deleted = 0 AND name='" . $txt_state . "'";
        return $query;
    }

    private function createStates()
    {
        if (!Configuration::get('CULQI_STATE_OK')) {
            $txt_state='Pago aceptado';
            $orderstate = Db::getInstance()->ExecuteS("SELECT distinct osl.id_order_state, osl.name FROM " . _DB_PREFIX_ . "order_state_lang osl, " . _DB_PREFIX_ . "order_state os where osl.id_order_state=os.id_order_state and osl.name='" . $txt_state . "' and deleted=0");
            Configuration::updateValue('CULQI_STATE_OK', (int)$orderstate[0]['id_order_state']);
        }
        if (!Configuration::get('CULQI_STATE_REFUND')) {
            $txt_state='Reembolsado';
            $orderstate = Db::getInstance()->ExecuteS("SELECT distinct osl.id_order_state, osl.name FROM " . _DB_PREFIX_ . "order_state_lang osl, " . _DB_PREFIX_ . "order_state os where osl.id_order_state=os.id_order_state and osl.name='" . $txt_state . "' and deleted=0");
            Configuration::updateValue('CULQI_STATE_REFUND', (int)$orderstate[0]['id_order_state']);
        }
        if (!Configuration::get('CULQI_STATE_PENDING')) {
            $txt_state = 'En espera de pago por Culqi';
            $rows = Db::getInstance()->getValue($this->queryGetStates($txt_state));
            if (intval($rows) == 0) {
                $order_state = new OrderState();
                $order_state->name = array();
                foreach (Language::getLanguages() as $language) {
                    $order_state->name[$language['id_lang']] = $txt_state;
                }
                $order_state->send_email = false;
                $order_state->color = '#34209E';
                $order_state->hidden = false;
                $order_state->paid = true;
                $order_state->module_name = 'culqi';
                $order_state->delivery = false;
                $order_state->logable = false;
                $order_state->invoice = true;
                $order_state->pdf_invoice = true;
                $order_state->add();
                Configuration::updateValue('CULQI_STATE_PENDING', (int)$order_state->id);
            } else {
                $orderstate = Db::getInstance()->ExecuteS("SELECT distinct id_order_state, name FROM " . _DB_PREFIX_ . "order_state_lang where name='" . $txt_state . "'");
                Configuration::updateValue('CULQI_STATE_PENDING', (int)$orderstate[0]['id_order_state']);
            }
        }
        if (!Configuration::get('CULQI_STATE_ERROR')) {
            $txt_state = 'Incorrecto - Culqi';
            $rows = Db::getInstance()->getValue($this->queryGetStates($txt_state));
            if (intval($rows) == 0) {
                $order_state = new OrderState();
                $order_state->name = array();
                foreach (Language::getLanguages() as $language) {
                    $order_state->name[$language['id_lang']] = $txt_state;
                }
                $order_state->send_email = false;
                $order_state->color = '#FF2843';
                $order_state->module_name = 'culqi';
                $order_state->hidden = false;
                $order_state->delivery = false;
                $order_state->logable = false;
                $order_state->invoice = false;
                $order_state->add();
                Configuration::updateValue('CULQI_STATE_ERROR', (int)$order_state->id);
            } else {
                $orderstate = Db::getInstance()->ExecuteS("SELECT distinct osl.id_order_state, osl.name FROM " . _DB_PREFIX_ . "order_state_lang osl, " . _DB_PREFIX_ . "order_state os where osl.id_order_state=os.id_order_state and osl.name='" . $txt_state . "' and deleted=0");
                Configuration::updateValue('CULQI_STATE_ERROR', (int)$orderstate[0]['id_order_state']);
            }
        }
        if (!Configuration::get('CULQI_STATE_EXPIRED')) {
            $txt_state = 'Expirado por Culqi';
            $rows = Db::getInstance()->getValue($this->queryGetStates($txt_state));
            if (intval($rows) == 0) {
                $order_state = new OrderState();
                $order_state->name = array();
                foreach (Language::getLanguages() as $language) {
                    $order_state->name[$language['id_lang']] = $txt_state;
                }
                $order_state->send_email = false;
                $order_state->color = '#ADADAD';
                $order_state->module_name = 'culqi';
                $order_state->hidden = false;
                $order_state->delivery = false;
                $order_state->logable = false;
                $order_state->invoice = false;
                $order_state->add();
                Configuration::updateValue('CULQI_STATE_EXPIRED', (int)$order_state->id);
            } else {
                $orderstate = Db::getInstance()->ExecuteS("SELECT distinct osl.id_order_state, osl.name FROM " . _DB_PREFIX_ . "order_state_lang osl, " . _DB_PREFIX_ . "order_state os where osl.id_order_state=os.id_order_state and osl.name='" . $txt_state . "' and deleted=0");
                Configuration::updateValue('CULQI_STATE_EXPIRED', (int)$orderstate[0]['id_order_state']);
            }
        }
    }

}

class CulqiPago
{
    public static $llaveSecreta;
    public static $codigoComercio;
}
