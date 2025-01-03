<?php

include_once dirname(__FILE__, 3) . '/culqi.php';

class CulqiRegisterSaleModuleFrontController extends ModuleFrontController
{	

    public function initContent()
    {
        parent::initContent();
        $this->ajax = false;
        $cart = $this->context->cart;
        $cartId = $cart->id;
        if (!$cart->id) {
            die(json_encode(['status' => 'error', 'message' => 'Cart is empty']));
        }
        // Check if an order already exists for this cart
        $id_order = Order::getOrderByCartId($cart->id);
        if ($id_order) {
            $order = new Order($id_order);
        } else {
            $customer = new Customer($cart->id_customer);
            $this->module->validateOrder((int)$cart->id, Configuration::get('CULQI_STATE_PENDING'), (float)$cart->getordertotal(true), 'Culqi', null, array(), (int)$cart->id_currency, false, $customer->secure_key);
            
            $id_order = Order::getOrderByCartId($this->context->cart->id);
            
            $order = new Order($id_order);
            $order_payment_collection = $order->getOrderPaymentCollection();
            
            $order_payment = $order_payment_collection[0];
            $order_payment->update();
        }
        
        $token = $this->generate_token();

        $gateway_url = $this->get_gateway_url($order, $token, $cart);
        $this->preserveCart($cartId);

        try{
            //die("llegamos bien");
        }catch (Exception $e){
            echo '<script type="text/javascript">console.log("Error en el update de cargo!"); </script>';
        }

        //die(json_encode($id_order));
        die(json_encode($gateway_url));
    }

    private function generate_token()
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

        $encryptedData = $this->encrypt_data_with_rsa(json_encode($data), $rsa_pk);
        
        return $encryptedData;
    }

    private function encrypt_data_with_rsa(string $jsonData, string $publicKeyString): ?string {
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

    private function get_gateway_url($order, $token, $cart)
    {
        $orderReference = $order->reference;
        $shopDomain = Tools::getShopDomainSsl(true);
        $apiUrl = CULQI_API_URL . 'shopify/public/save-order';
        $platform = "prestashop";

        $currency = new Currency($order->id_currency);
        $customer = new Customer($order->id_customer);

        $deliveryAddress = new Address((int)$order->id_address_delivery);
        $billingAddress = new Address((int)$order->id_address_invoice);

        $body = array(
            "id" => $order->id,
            "platform" => $platform,
            "gid" => "gid://prestashop/PaymentSession/" . $order->id,
            "amount" => number_format($order->total_paid, 2, '.', ''),
            "currency" => $currency->iso_code,
            "proposed_at" => gmdate('Y-m-d\TH:i:s'),
            "kind" => "sale",
            "test" => true,
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
                "email" => $customer->email,
                "locale" => "en-PE"
            ),
            "cancel_url" => $this->context->link->getPageLink('order'),
            "success_url" => 'index.php?controller=order-confirmation&id_cart=' . (int)$cart->id . '&id_module=' . (int)$this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key,
            "merchant_locale" => "en-PE",
            "shop_domain" => $shopDomain,
            "order_key" => $orderReference,
        );
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
        curl_close($ch);

        // Process response
        if ($httpCode != 200 || !$response) {
            PrestaShopLogger::addLog('Payment error: Could not connect to the payment gateway.', 3);
            return array(
                'result' => 'failure',
                'message' => 'Payment error: Could not connect to the payment gateway.'
            );
        }

        $result = json_decode($response, true);

        if (isset($result['redirect_url'])) {
            $gatewayUrl = $result['redirect_url'];

            // Update order status to pending
            $order->setCurrentState(Configuration::get('CULQI_STATE_PENDING'));
            $order->save();

            return array(
                'result' => 'success',
                'show_modal' => true,
                'redirect' => $gatewayUrl
            );
        } else {
            PrestaShopLogger::addLog('Payment error: Invalid response from payment gateway.', 3);
            return array(
                'result' => 'failure',
                'message' => 'Payment error: Invalid response from payment gateway.'
            );
        }
    }

    private function preserveCart($cartId)
    {
        if ($cartId) {
            Context::getContext()->cart = new Cart($cartId);
            Context::getContext()->cookie->id_cart = $cartId;
            Context::getContext()->cookie->write();
            Context::getContext()->cart->update();
            if (!Validate::isLoadedObject(Context::getContext()->cart)) {
                die(json_encode(['status' => 'error', 'message' => 'Failed to restore cart.']));
            }
        } else {
            die(json_encode(['status' => 'error', 'message' => 'Failed to preserve cart. Cart ID is missing.']));
        }
    }

}