<?php

include_once dirname(__FILE__, 3) . '/culqi.php';

class CulqiRegisterSaleModuleFrontController extends ModuleFrontController
{
    private $logger;

    public function __construct()
    {
        parent::__construct();
        $this->logger = CulqiLogger::get_instance();
    }

    public function initContent()
    {
        parent::initContent();
        $this->ajax = false;
        $cart = $this->context->cart;

        $this->logger->info('Checkout', '[registersale] Starting payment process', ['cart_id' => $cart->id]);

        if (!$cart->id) {
            $this->logger->warning('Checkout', '[registersale] Cart is empty');
            die(json_encode(['status' => 'error', 'message' => 'Cart is empty']));
        }

        $customer = new Customer($cart->id_customer);
        $token = generate_token();

        $gateway_url = $this->get_gateway_url($cart, $token);

        try{
            //die("llegamos bien");
        }catch (Exception $e){
            $this->logger->error('Checkout', '[registersale] Exception in register sale', ['error' => $e->getMessage()]);
            echo '<script type="text/javascript">console.log("Error en el update de cargo!"); </script>';
        }

        //die(json_encode($id_order));
        die(json_encode($gateway_url));
    }

    private function get_gateway_url($cart, $token)
    {
        $this->logger->debug('Checkout', '[registersale] Building gateway URL', ['cart_id' => $cart->id]);

        $carrierName = 'No method selected';
        if ((int) $cart->id_carrier > 0) {
            $carrier = new Carrier((int) $cart->id_carrier);
            if (Validate::isLoadedObject($carrier)) {
                $carrierName = $carrier->name;
            }
        }

        $shippingTotalTaxIncl = (float) $cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
        $shippingTotalTaxExcl = (float) $cart->getOrderTotal(false, Cart::ONLY_SHIPPING);
        $shippingTax = $shippingTotalTaxIncl - $shippingTotalTaxExcl;

        $orderReference = '';
        $shopDomain = Tools::getShopDomainSsl();
        $apiUrl = CULQI_API_URL . 'shopify/public/save-order';
        $platform = PLATFORM;
        $user_agent = Tools::getValue('HTTP_USER_AGENT', $_SERVER['HTTP_USER_AGENT']);

        $currency = $this->context->currency;
        $customer = $this->context->customer;

        $deliveryAddress = new Address((int)$cart->id_address_delivery);
        $billingAddress = new Address((int)$cart->id_address_invoice);
        $env = $this->get_env();

        $this->logger->debug('Checkout', '[registersale] Environment check', ['env' => $env]);

        $themeName = '';
        $themeVersion = '';

        if (isset($this->context->shop) && !empty($this->context->shop->theme_name)) {
            $themeName = (string) $this->context->shop->theme_name;
        }

        $body = array(
            "id" => $cart->id,
            "platform" => $platform,
            "gid" => "gid://prestashop/PaymentSession/" . $cart->id,
            "amount" => number_format($cart->getOrderTotal(true, Cart::BOTH), 2, '.', ''),
            "currency" => $currency->iso_code,
            "proposed_at" => gmdate('Y-m-d\TH:i:s'),
            "kind" => "sale",
            "test" => $env,
            "payment_method" => array(
                "type" => "offsite",
                "data" => array(
                    "cancel_url" => $this->context->link->getPageLink('order')
                )
            ),
            "customer" => array(
                "billing_address" => array(
                    'given_name' => $billingAddress->firstname,
                    'family_name' => $billingAddress->lastname,
                    'line1' => $billingAddress->address1,
                    'line2' => $billingAddress->address2,
                    'city' => $billingAddress->city,
                    'postal_code' => $billingAddress->postcode,
                    'province' => State::getNameById($billingAddress->id_state),
                    'country_code' => Country::getIsoById($billingAddress->id_country),
                ),
                "shipping_address" => array(
                    "given_name" => $customer->firstname,
                    "family_name" => $customer->lastname,
                    "line1" => $deliveryAddress->address1,
                    "line2" => $deliveryAddress->address2,
                    "city" => $deliveryAddress->city,
                    "postal_code" => $deliveryAddress->postcode,
                    "province" => State::getNameById($deliveryAddress->id_state),
                    "country_code" => Country::getIsoById($deliveryAddress->id_country)
                ),
                "shipping_data" => array(
                    "method" => $carrierName ?: 'No method selected',
                    "total" => (string) number_format($shippingTotalTaxExcl, 2, '.', ''),
                    "tax" => (string) number_format($shippingTax, 2, '.', ''),
                ),
                "email" => $customer->email,
                "locale" => "en-PE"
            ),
            "cancel_url" => $this->context->link->getPageLink('order'),
            "merchant_locale" => "en-PE",
            "shop_domain" => $shopDomain,
            "order_key" => $customer->secure_key,
            "phone" => $billingAddress->phone ?: '',
            "browser" => $user_agent,
            "products" => $this->get_cart_products($cart),
            "audit_data" => array(
                "integration_type"=> 'plugin',
                "ip"=>  $this->obtener_ip_real(),
                "user_agent" =>  $user_agent,
                "checkout_version" => CHECKOUT_VERSION,
                "threeds" => CULQI_3DS,
                "plugin_version" => CULQI_PLUGIN_VERSION,
                "cms" => $platform,
                "cms_version" => _PS_VERSION_,
                "php_version" => PHP_VERSION,
                "name_theme" => $themeName,
                "version_theme" => $themeVersion,
                "url_theme" => isset($this->context->shop->theme_name) ? $this->context->shop->theme_name : '',
            ),
        );

        $this->logger->info('Checkout', '[registersale] Sending API request', [
            'api_url' => $apiUrl,
            'cart_id' => $cart->id,
            'amount' => $body['amount'],
            'currency' => $body['currency'],
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'shopify-shop-domain: ' . $shopDomain,
            'Authorization: Bearer ' . $token,
        ));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $this->logger->info('Checkout', '[registersale] API response received', [
            'http_code' => $httpCode,
            'response_length' => strlen($response),
        ]);

        // Process response
        if ($httpCode != 200 || !$response) {
            $this->logger->error('Checkout', '[registersale] Could not connect to gateway', [
                'http_code' => $httpCode,
                'curl_error' => $curlError,
            ]);
            return array(
                'result' => 'failure',
                'message' => 'Payment error: Could not connect to the payment gateway.'
            );
        }

        $result = json_decode($response, true);

        if (isset($result['redirect_url'])) {
            $gatewayUrl = $result['redirect_url'];

            $this->logger->info('Checkout', '[registersale] Payment success, redirecting', [
                'redirect_url' => substr($gatewayUrl, 0, 100) . '...',
            ]);

            return array(
                'result' => 'success',
                'show_modal' => true,
                'redirect' => $this->formatGatewayUrl($gatewayUrl)
            );
        } else {
            $this->logger->warning('Checkout', '[registersale] Invalid response - no redirect_url', [
                'response_preview' => substr($response, 0, 200),
            ]);
            return array(
                'result' => 'failure',
                'message' => 'Payment error: Invalid response from payment gateway.'
            );
        }
    }

    private function get_cart_products($cart)
    {
        $products = $cart->getProducts();
        if (empty($products)) {
            $this->logger->debug('Checkout', '[registersale] Cart has no products');
            return null;
        }

        $items = array();
        foreach ($products as $product) {
            $quantity = (int) $product['quantity'];
            $line_total = (float) $product['price_wt'] * $quantity;
            $unit_price = $quantity > 0 ? $line_total / $quantity : (float) $product['price_wt'];

            $items[] = array(
                'name' => $product['name'] ?? '',
                'quantity' => $quantity > 0 ? $quantity : 1,
                'unit_price' => number_format($unit_price, 2, '.', ''),
            );
        }

        $this->logger->debug('Checkout', '[registersale] Cart products processed', ['product_count' => count($items)]);
        return $items;
    }

    private function get_env()
    {
        $public_key = Configuration::get('CULQI_LLAVE_PUBLICA') ?? '';
        if(!$public_key) {
            return array(
                'result' => 'failure',
                'message' => 'Debes configurar tu llave pública.'
            );
        }

        if (str_starts_with($public_key, 'pk_test')) {
            return 'test';
        } elseif (str_starts_with($public_key, 'pk_live')) {
            return 'live';
        }

        return false;
    }

    private function formatGatewayUrl(string $url): string {
        return $url . '&culqiPluginVersion=' . CULQI_PLUGIN_VERSION . '&culqiClientVersion=' . _PS_VERSION_;
    }

    private function obtener_ip_real() {
        if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] )[0];
        } elseif ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
}
