<?php

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_'))
    exit;

define('CULQI_PLUGIN_VERSION', '3.1.2');

define('URLAPI_INTEG', 'https://ag-plugins.culqi.com');
define('URLAPI_PROD', 'https://ag-plugins.culqi.com');

define('URLAPI_INTEG_3DS', 'https://3ds.culqi.com');
define('URLAPI_PROD_3DS', 'https://3ds.culqi.com');

define('URLAPI_ORDERCHARGES_INTEG', 'https://api.culqi.com/v2');
define('URLAPI_CHECKOUT_INTEG', 'https://js.culqi.com/checkout-js');

define('URLAPI_LOGIN_INTEG', URLAPI_INTEG.'/plugins/public/login');
define('URLAPI_MERCHANT_INTEG', URLAPI_INTEG.'/plugins/public/get_merchants');
define('URLAPI_MERCHANTSINGLE_INTEG', URLAPI_INTEG.'/plugins/public/get_merchant?public_key=');
define('URLAPI_WEBHOOK_INTEG', URLAPI_INTEG.'/plugins/public/webhook');

define('URLAPI_ORDERCHARGES_PROD', 'https://api.culqi.com/v2');
define('URLAPI_CHECKOUT_PROD', 'https://js.culqi.com/checkout-js');

define('URLAPI_LOGIN_PROD', URLAPI_PROD.'/plugins/public/login');
define('URLAPI_MERCHANT_PROD', URLAPI_PROD.'/plugins/public/get_merchants'); 
define('URLAPI_MERCHANTSINGLE_PROD', URLAPI_PROD.'/plugins/public/get_merchant?public_key=');
define('URLAPI_WEBHOOK_PROD', URLAPI_PROD.'/plugins/public/webhook');

define('LOADER_IMG', 'https://icon-library.com/images/loading-icon-transparent-background/loading-icon-transparent-background-12.jpg');

/**
 * Calling dependencies
 */
include_once dirname(__FILE__) . '/libraries/Requests/library/Requests.php';

Requests::register_autoloader();

//include_once dirname(__FILE__).'/libraries/culqi-php/lib/culqi.php';
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
            Configuration::updateValue('CULQI_ENVIROMENT', '') &&
            Configuration::updateValue('CULQI_LLAVE_SECRETA', '') &&
            Configuration::updateValue('CULQI_LLAVE_PUBLICA', '') &&
            Configuration::updateValue('CULQI_METHODS_TARJETA', '') &&
            Configuration::updateValue('CULQI_METHODS_BANCAMOVIL', '') &&
            Configuration::updateValue('CULQI_METHODS_AGENTS', '') &&
            Configuration::updateValue('CULQI_METHODS_WALLETS', '') &&
            Configuration::updateValue('CULQI_METHODS_QUOTEBCP', '') &&
            Configuration::updateValue('CULQI_TIMEXP', '') &&
            Configuration::updateValue('CULQI_NOTPAY', '') &&
            Configuration::updateValue('CULQI_USERNAME', '') &&
            Configuration::updateValue('CULQI_PASSWORD', '') &&
            Configuration::updateValue('CULQI_URL_LOGO', '') &&
            Configuration::updateValue('CULQI_COLOR_PALETTE', '') && 
            Configuration::updateValue('CULQI_RSA_ID', '') && 
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

            $this->context->controller->registerJavascript(
                'culqiv4',
                $this->getCulqiInfoCheckout(true)['enviroment_fronted'],
                array('server' => 'remote', 'position' => 'bottom', 'priority' => 10000)
            );
            $this->context->controller->registerJavascript(
                'culqiwaitme',
                $this->_path.'views/js/waitMe.min.js',
                array('server' => 'remote', 'position' => 'bottom', 'priority' => 10000)
            );
            $this->context->controller->registerJavascript(
                'culqi3ds',
                $this->getCulqiInfoCheckout(true)['enviroment_3ds'],
                array('server' => 'remote', 'position' => 'bottom', 'priority' => 10000)
            );

            $data = json_encode($this->getCulqiInfoCheckout(false, true));
            $jsCode = "<script>
                var phpData = {$data};
            </script>";

            $this->context->controller->registerJavascript(
                'brandFunctions',
                $this->_path.'views/js/brand-handle.js?_='.time(),
                array(
                    'server' => 'remote', 
                    'position' => 'bottom', 
                    'priority' => 10000
                )
            );
            
            $this->context->controller->registerJavascript(
                'culqifunctions',
                $this->_path.'views/js/culqi.js?_='.time(),
                array('server' => 'remote', 'position' => 'bottom', 'priority' => 10000)
            );

            $this->context->controller->registerJavascript(
                'sonicMastercard',
                $this->_path.'views/brands/mastercard/mc-sonic.min.js?_='.time(),
                array('server' => 'remote', 'position' => 'bottom', 'priority' => 10000)
            );
            
            $this->context->controller->registerJavascript(
                'sonicVisa',
                $this->_path.'views/brands/visa/VisaSensoryBrandingSDK/visa-sensory-branding.js?_='.time(),
                array('server' => 'remote', 'position' => 'bottom', 'priority' => 10000)
            );

            $this->context->controller->registerStylesheet(
                'globalCss',
                $this->_path.'views/css/global.css',
                [
                  'media' => 'all',
                  'priority' => 200,
                ]
            );
            
            $this->context->controller->registerStylesheet(
                'brandCss',
                $this->_path.'views/css/brands.css',
                [
                  'media' => 'all',
                  'priority' => 200,
                ]
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

    private function getAddress($address)
    {
        if (empty($address->address1)) {
            return $address->address2;
        } else {
            return $address->address1;
        }
    }

    private function getPhone($address)
    {
        if (empty($address->phone_mobile)) {
            return $address->phone;
        } else {
            return $address->phone_mobile;
        }
    }

    private function getCustomerId()
    {
        if ($this->context->customer->isLogged()) {
            return (int)$this->context->customer->id;
        } else {
            return 0;
        }
    }

    public function errorPayment($mensaje)
    {
        $smarty = $this->context->smarty;
        $smarty->assign('culqi_error_pago', $mensaje);
    }

    public function charge($token_id, $installments)
    {

        try {

            $cart = $this->context->cart;

            $userAddress = new Address((int)$cart->id_address_invoice);
            $userCountry = new Country((int)$userAddress->id_country);

            $culqi = new Culqi\Culqi(array('api_key' => Configuration::get('CULQI_LLAVE_SECRETA')));

            $charge = $culqi->Charges->create(
                array(
                    "amount" => $this->removeComma($cart->getOrderTotal(true, Cart::BOTH)),
                    "antifraud_details" => array(
                        "address" => $this->getAddress($userAddress),
                        "address_city" => $userAddress->city,
                        "country_code" => "PE",
                        "first_name" => $this->context->customer->firstname,
                        "last_name" => $this->context->customer->lastname,
                        "phone_number" => $this->getPhone($userAddress)
                    ),
                    "capture" => true,
                    "currency_code" => $this->context->currency->iso_code,
                    "description" => "Orden de compra " . $cart->id,
                    "installments" => $installments,
                    "metadata" => array("order_id" => (string)$cart->id),
                    "email" => $this->context->customer->email,
                    "source_id" => $token_id
                )
            );
            //return $cargo;
            return $charge;
        } catch (Exception $e) {
            return $e->getMessage();
        }

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
        //var_dump($this->getCulqiInfoCheckout()); exit(1);
        if ($this->getConfigFieldsValues()['CULQI_ENABLED'] == 'yes') {
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
    public function getCulqiInfoCheckout($is_header = false, $is_checkout = false)
    {
        $urlapi_ordercharges = URLAPI_ORDERCHARGES_INTEG;
        $urlapi_checkout = URLAPI_CHECKOUT_INTEG;
        $urlapi_3ds = URLAPI_INTEG_3DS;
        if (Configuration::get('CULQI_ENVIROMENT') == 'prod') {
            $urlapi_ordercharges = URLAPI_ORDERCHARGES_PROD;
            $urlapi_checkout = URLAPI_CHECKOUT_PROD;
            $urlapi_3ds = URLAPI_PROD_3DS;
        }

        if($is_header) {
            return array(
                "enviroment_fronted" => $urlapi_checkout,
                "enviroment_3ds" => $urlapi_3ds,
                "jquery" => 'https://code.jquery.com/jquery-3.6.0.min.js'
            );
        }

        $cart = $this->context->cart;
        $address = Db::getInstance()->ExecuteS("SELECT * FROM " . _DB_PREFIX_ . "address where id_address=" . $cart->id_address_invoice);
        if($address) {
            $country = Db::getInstance()->ExecuteS("SELECT * FROM " . _DB_PREFIX_ . "country where id_country=" . $address[0]['id_country']);
        }
        $total = $cart->getOrderTotal(true, Cart::BOTH);
        $color_palette = Configuration::get('CULQI_COLOR_PALETTE');
        if(count(explode('-', $color_palette)) > 1) {
            $color_arr = explode('-', $color_palette);
        } else {
            $color_arr = [];
            $color_arr[0] = "";
            $color_arr[1] = "";
        }

        $https = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? $_SERVER['HTTP_X_FORWARDED_PROTO'] : null;
        if (is_null($https)) {
            $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        }
        $base_url = $https . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        
        $checkout_data =  array(
            "psversion" => $this->ps_versions_compliancy['max'],
            "total" => $total * 100,
            "llave_publica" => Configuration::get('CULQI_LLAVE_PUBLICA'),
            "tarjeta" => Configuration::get('CULQI_METHODS_TARJETA') == 'yes' ? 'true' : 'false',
            "banca_movil" => Configuration::get('CULQI_METHODS_BANCAMOVIL') == 'yes' ? 'true' : 'false',
            "yape" => Configuration::get('CULQI_METHODS_YAPE') == 'yes' ? 'true' : 'false',
            "billetera" => Configuration::get('CULQI_METHODS_WALLETS') == 'yes' ? 'true' : 'false',
            "agente" => Configuration::get('CULQI_METHODS_AGENTS') == 'yes' ? 'true' : 'false',
            "cuetealo" => Configuration::get('CULQI_METHODS_QUOTEBCP') == 'yes' ? 'true' : 'false',
            "url_logo" => Configuration::get('CULQI_URL_LOGO'),
            "rsa_id" => Configuration::get('CULQI_RSA_ID'),
            "rsa_pk" => Configuration::get('CULQI_RSA_PK'),
            "color_pallete" => $color_arr,
            "currency" => $this->context->currency->iso_code,
            'commerce' => Configuration::get('PS_SHOP_NAME'),
            "BASE_URL" => $base_url,
            'CULQI_PLUGIN_VERSION' => 'v'.CULQI_PLUGIN_VERSION,
            'generate_order_url' => $this->context->link->getModuleLink('culqi', 'generateorder', []),
            'chargeajax_url' => $this->context->link->getModuleLink('culqi', 'chargeajax', []),
            'postpayment_url' => $this->context->link->getModuleLink('culqi', 'postpayment', []),
            'postpaymentpending_url' => $this->context->link->getModuleLink('culqi', 'postpaymentpending', []),
            'registersale_url' => $this->context->link->getModuleLink('culqi', 'registersale', []),
            'email'=> $this->context->customer->email
        );

        if($is_checkout) {
            return $checkout_data;
        }

        $info_checkout = array(
            "module_dir" => $this->_path,
            "descripcion" => "Orden de compra " . $cart->id,
            "orden" => $cart->id,
            "enviroment_backend" => $urlapi_ordercharges,
            "enviroment_fronted" => $urlapi_checkout,
            "enviroment_3ds" => $urlapi_3ds,
            "llave_secreta" => Configuration::get('CULQI_LLAVE_SECRETA'),
            "tiempo_exp" => (Configuration::get('CULQI_TIMEXP') == '' ? 24 : Configuration::get('CULQI_TIMEXP')),
            "address" => $address,
            "customer" => $this->context->customer,
            "firstname" => $this->context->customer->firstname,
            "lastname" => $this->context->customer->lastname,
            'country'=>$country ?? "PE"
        );

        return array_merge($checkout_data, $info_checkout);
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
            || !Configuration::deleteByName('CULQI_ENVIROMENT')
            || !Configuration::deleteByName('CULQI_LLAVE_SECRETA')
            || !Configuration::deleteByName('CULQI_LLAVE_PUBLICA')
            || !Configuration::deleteByName('CULQI_METHODS_TARJETA')
            || !Configuration::deleteByName('CULQI_METHODS_BANCAMOVIL')
            || !Configuration::deleteByName('CULQI_METHODS_YAPE')
            || !Configuration::deleteByName('CULQI_METHODS_AGENTS')
            || !Configuration::deleteByName('CULQI_METHODS_WALLETS')
            || !Configuration::deleteByName('CULQI_METHODS_QUOTEBCP')
            || !Configuration::deleteByName('CULQI_TIMEXP')
            || !Configuration::deleteByName('CULQI_NOTPAY')
            || !Configuration::deleteByName('CULQI_USERNAME')
            || !Configuration::deleteByName('CULQI_PASSWORD')
            || !Configuration::deleteByName('CULQI_URL_LOGO')
            || !Configuration::deleteByName('CULQI_COLOR_PALETTE')
            || !Configuration::deleteByName('CULQI_RSA_ID')
            || !Configuration::deleteByName('CULQI_RSA_PK')
            || !$this->uninstallStates())
            return false;
        return true;
    }

    private function _postValidation()
    {
        if (Tools::isSubmit('btnSubmit')) {
            if (!Tools::getValue('CULQI_LLAVE_SECRETA')) {
                $this->_postErrors[] = $this->l('El campo llave de comercio es requerido.');
            }

            if (!Tools::getValue('CULQI_LLAVE_PUBLICA')) {
                $this->_postErrors[] = $this->l('El campo código de comercio es requerido.');
            }
        }
    }

    private function _displayInfo()
    {
        return $this->display(__FILE__, 'info.tpl');
    }

    public function getContent()
    {

        $this->_html = '';

        if (Tools::isSubmit('btnSubmit')) {
            $this->_postValidation();
            if (!count($this->_postErrors)) {
                $this->_postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_html .= $this->displayError($err);
                }
            }
        }

        //$this->_html .= $this->_displayInfo();
        $this->_html .= $this->renderForm();

        return $this->_html;
    }

    private function createStates()
    {
        if (!Configuration::get('CULQI_STATE_OK')) {
            $txt_state='Pago aceptado';
            $orderstate = Db::getInstance()->ExecuteS("SELECT distinct osl.id_order_state, osl.name FROM " . _DB_PREFIX_ . "order_state_lang osl, " . _DB_PREFIX_ . "order_state os where osl.id_order_state=os.id_order_state and osl.name='" . $txt_state . "' and deleted=0");
            Configuration::updateValue('CULQI_STATE_OK', (int)$orderstate[0]['id_order_state']);
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
    
    private function queryGetStates($txt_state)
    {
	$query = "SELECT count(*) as filas FROM  " . _DB_PREFIX_ . "order_state a,  " . _DB_PREFIX_ . "order_state_lang b WHERE b.id_order_state = a.id_order_state AND a.deleted = 0 AND name='" . $txt_state . "'";
	return $query;
    }

    /**
     * Admin Zone
     */

    public function renderForm()
    {
        $config = $this->getConfigFieldsValues();
        $this->context->smarty->assign(array(
            'currentIndex' => $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name,
            'token' => Tools::getAdminTokenLite('AdminModules'),
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
            'status_enabled' => $config['CULQI_ENABLED'] == 'yes' ? 'checked' : '',
            'status_methods_tarjeta_enabled' => $config['CULQI_METHODS_TARJETA'] == 'yes' ? 'checked' : '',
            'status_methods_bancamovil_enabled' => $config['CULQI_METHODS_BANCAMOVIL'] == 'yes' ? 'checked' : '',
            'status_methods_yape_enabled' => $config['CULQI_METHODS_YAPE'] == 'yes' ? 'checked' : '',
            'status_methods_agents_enabled' => $config['CULQI_METHODS_AGENTS'] == 'yes' ? 'checked' : '',
            'status_methods_wallets_enabled' => $config['CULQI_METHODS_WALLETS'] == 'yes' ? 'checked' : '',
            'status_methods_quotebcp_enabled' => $config['CULQI_METHODS_QUOTEBCP'] == 'yes' ? 'checked' : ''
        ));
        //var_dump(__FILE__); exit(1);
        return $this->display(__FILE__, '/views/templates/hook/setting.tpl');
    }

    public function generate_username() {
        $username_bd = Tools::getValue('CULQI_USERNAME', Configuration::get('CULQI_USERNAME'));
        if($username_bd == '' || $username_bd == null){
            return bin2hex(random_bytes(5));
        }
        return $username_bd;
    }
    public function generate_password() {
        $password_bd = Tools::getValue('CULQI_PASSWORD', Configuration::get('CULQI_PASSWORD'));
        if($password_bd == '' || $password_bd == null){
            return bin2hex(random_bytes(10));
        }
        return $password_bd;
    }
    public function getConfigFieldsValues()
    {
        $checked_integ = 'checked="true"';
        $checked_prod = '';
        $urlapi_login = URLAPI_LOGIN_INTEG;
        $urlapi_merchant = URLAPI_MERCHANT_INTEG;
        $urlapi_merchantsingle = URLAPI_MERCHANTSINGLE_INTEG;
        $urlapi_webhook = URLAPI_WEBHOOK_INTEG;
        if (Configuration::get('CULQI_ENVIROMENT') == 'prod') {
            $checked_integ = '';
            $checked_prod = 'checked="true"';
            $urlapi_login = URLAPI_LOGIN_PROD;
            $urlapi_merchant = URLAPI_MERCHANT_PROD;
            $urlapi_merchantsingle = URLAPI_MERCHANTSINGLE_PROD;
            $urlapi_webhook = URLAPI_WEBHOOK_PROD;
        }
        $post = 0;
        if (isset($_GET['tab_module']) and $_GET['tab_module'] == 'payments_gateways') {
            $post = 1;
        }
        $username = $this->generate_username();
        $password = $this->generate_password();
        $errors = count($this->_postErrors);
        return array(
            'CULQI_ENABLED' => Tools::getValue('CULQI_ENABLED', Configuration::get('CULQI_ENABLED')),
            'CULQI_ENVIROMENT' => Tools::getValue('CULQI_ENVIROMENT', Configuration::get('CULQI_ENVIROMENT')),
            'CULQI_LLAVE_SECRETA' => Tools::getValue('CULQI_LLAVE_SECRETA', Configuration::get('CULQI_LLAVE_SECRETA')),
            'CULQI_LLAVE_PUBLICA' => Tools::getValue('CULQI_LLAVE_PUBLICA', Configuration::get('CULQI_LLAVE_PUBLICA')),
            'CULQI_METHODS_TARJETA' => Tools::getValue('CULQI_METHODS_TARJETA', Configuration::get('CULQI_METHODS_TARJETA')),
            'CULQI_METHODS_BANCAMOVIL' => Tools::getValue('CULQI_METHODS_BANCAMOVIL', Configuration::get('CULQI_METHODS_BANCAMOVIL')),
            'CULQI_METHODS_YAPE' => Tools::getValue('CULQI_METHODS_YAPE', Configuration::get('CULQI_METHODS_YAPE')),
            'CULQI_METHODS_AGENTS' => Tools::getValue('CULQI_METHODS_AGENTS', Configuration::get('CULQI_METHODS_AGENTS')),
            'CULQI_METHODS_WALLETS' => Tools::getValue('CULQI_METHODS_WALLETS', Configuration::get('CULQI_METHODS_WALLETS')),
            'CULQI_METHODS_QUOTEBCP' => Tools::getValue('CULQI_METHODS_QUOTEBCP', Configuration::get('CULQI_METHODS_QUOTEBCP')),
            'CULQI_TIMEXP' => Tools::getValue('CULQI_TIMEXP', Configuration::get('CULQI_TIMEXP')),
            'CULQI_NOTPAY' => Tools::getValue('CULQI_NOTPAY', Configuration::get('CULQI_NOTPAY')),
            'CULQI_USERNAME' =>$username,
            'CULQI_PASSWORD' =>$password,
            'CULQI_URL_LOGO' => Tools::getValue('CULQI_URL_LOGO', Configuration::get('CULQI_URL_LOGO')),
            'CULQI_RSA_ID' => Tools::getValue('CULQI_RSA_ID', Configuration::get('CULQI_RSA_ID')),
            'CULQI_RSA_PK' => Tools::getValue('CULQI_RSA_PK', Configuration::get('CULQI_RSA_PK')),
            'CULQI_COLOR_PALETTE' => Tools::getValue('CULQI_COLOR_PALETTE', Configuration::get('CULQI_COLOR_PALETTE')),
            'CULQI_COLOR_PALETTEID' => str_replace('#', '', Tools::getValue('CULQI_COLOR_PALETTE', Configuration::get('CULQI_COLOR_PALETTE'))),
            'CULQI_CHECKED_INTEG' => $checked_integ,
            'CULQI_CHECKED_PROD' => $checked_prod,
            'CULQI_URL_LOGIN' => $urlapi_login,
            'CULQI_URL_MERCHANT' => $urlapi_merchant,
            'CULQI_URL_MERCHANTSINGLE' => $urlapi_merchantsingle,
            'CULQI_URL_WEBHOOK' => $urlapi_webhook,
            'CULQI_URL_WEBHOOK_PS' => $this->context->link->getModuleLink($this->name, 'webhook', array()),
            'CULQI_POST' => $post,
            'URLAPI_LOGIN_INTEG' => URLAPI_LOGIN_INTEG,
            'URLAPI_MERCHANT_INTEG' => URLAPI_MERCHANT_INTEG,
            'URLAPI_MERCHANTSINGLE_INTEG' => URLAPI_MERCHANTSINGLE_INTEG,
            'URLAPI_WEBHOOK_INTEG' => URLAPI_WEBHOOK_INTEG,
            'URLAPI_LOGIN_PROD' => URLAPI_LOGIN_PROD,
            'URLAPI_MERCHANT_PROD' => URLAPI_MERCHANT_PROD,
            'URLAPI_MERCHANTSINGLE_PROD' => URLAPI_MERCHANTSINGLE_PROD,
            'URLAPI_WEBHOOK_PROD' => URLAPI_WEBHOOK_PROD,
            'CULQI_PLUGIN_VERSION' => CULQI_PLUGIN_VERSION,
            'CULQI_POST_ERRORS' => $errors,
            'commerce' => Configuration::get('PS_SHOP_NAME')
        );
    }

    private function _postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('CULQI_ENABLED', Tools::getValue('CULQI_ENABLED'));
            Configuration::updateValue('CULQI_ENVIROMENT', Tools::getValue('CULQI_ENVIROMENT'));
            Configuration::updateValue('CULQI_LLAVE_SECRETA', Tools::getValue('CULQI_LLAVE_SECRETA'));
            Configuration::updateValue('CULQI_LLAVE_PUBLICA', Tools::getValue('CULQI_LLAVE_PUBLICA'));
            Configuration::updateValue('CULQI_METHODS_TARJETA', Tools::getValue('CULQI_METHODS_TARJETA'));
            Configuration::updateValue('CULQI_METHODS_BANCAMOVIL', Tools::getValue('CULQI_METHODS_BANCAMOVIL'));
            Configuration::updateValue('CULQI_METHODS_YAPE', Tools::getValue('CULQI_METHODS_YAPE'));
            Configuration::updateValue('CULQI_METHODS_AGENTS', Tools::getValue('CULQI_METHODS_AGENTS'));
            Configuration::updateValue('CULQI_METHODS_WALLETS', Tools::getValue('CULQI_METHODS_WALLETS'));
            Configuration::updateValue('CULQI_METHODS_QUOTEBCP', Tools::getValue('CULQI_METHODS_QUOTEBCP'));
            Configuration::updateValue('CULQI_TIMEXP', Tools::getValue('CULQI_TIMEXP'));
            Configuration::updateValue('CULQI_NOTPAY', Tools::getValue('CULQI_NOTPAY'));
            Configuration::updateValue('CULQI_USERNAME', Tools::getValue('CULQI_USERNAME'));
            Configuration::updateValue('CULQI_PASSWORD', Tools::getValue('CULQI_PASSWORD'));
            Configuration::updateValue('CULQI_URL_LOGO', Tools::getValue('CULQI_URL_LOGO'));
            Configuration::updateValue('CULQI_COLOR_PALETTE', Tools::getValue('CULQI_COLOR_PALETTE'));
            Configuration::updateValue('CULQI_RSA_ID', Tools::getValue('CULQI_RSA_ID'));
            Configuration::updateValue('CULQI_RSA_PK', Tools::getValue('CULQI_RSA_PK'));
        }
        $this->_html .= $this->displayConfirmation($this->l('Se actualizaron las configuraciones'));
    }

    public function removeComma($amount)
    {
        return str_replace(".", "", str_replace(',', '', number_format($amount, 2, '.', ',')));
    }

}


class CulqiPago
{
    public static $llaveSecreta;
    public static $codigoComercio;
}
