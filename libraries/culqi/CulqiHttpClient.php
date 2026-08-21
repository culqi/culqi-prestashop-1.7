<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/CulqiLogger.php';

class CulqiHttpClient
{
    private $logger;
    private $timeout = 60;
    private $baseUrl;

    public function __construct($timeout = 60)
    {
        $this->logger = CulqiLogger::get_instance();
        $this->timeout = $timeout;
        $this->baseUrl = CULQI_API_URL;
    }

    private function get_user_agent(): string
    {
        $ua = Tools::getValue('HTTP_USER_AGENT', $_SERVER['HTTP_USER_AGENT']);
        if (empty($ua) || strlen($ua) < 10) {
            return 'plugin-prestashop/' . CULQI_PLUGIN_VERSION;
        }
        return $ua;
    }

    private function build_headers(array $custom = []): array
    {
        $defaults = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => $this->get_user_agent(),
        ];

        foreach ($custom as $key => $value) {
            $defaults[$key] = $value;
        }

        $headers = [];
        foreach ($defaults as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }

        return $headers;
    }

    private function request(string $method, string $endpoint, ?array $data = null, array $headers = []): array
    {
        $url = $this->baseUrl . $endpoint;
        $body = $data !== null ? json_encode($data) : null;
        $requestHeaders = $this->build_headers($headers);

        $this->logger->info('HttpClient', '[request] Preparing HTTP request', [
            'method' => $method,
            'url' => $url,
            'headers' => $requestHeaders,
            'body' => $body,
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($body !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                }
                break;
            case 'PUT':
            case 'PATCH':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
                if ($body !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                }
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if ($body !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                }
                break;
            case 'GET':
            default:
                if ($body !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                }
                break;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $this->logger->info('HttpClient', '[request] HTTP response received', [
            'url' => $url,
            'http_code' => $httpCode,
            'response_length' => strlen($response),
        ]);

        if ($curlError) {
            $this->logger->error('HttpClient', '[request] cURL error', [
                'url' => $url,
                'error' => $curlError,
            ]);
            return [
                'success' => false,
                'data' => null,
                'http_code' => 0,
                'error' => $curlError,
            ];
        }

        $decodedResponse = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'data' => $decodedResponse ?? $response,
                'http_code' => $httpCode,
            ];
        }

        $this->logger->warning('HttpClient', '[request] HTTP error response', [
            'url' => $url,
            'http_code' => $httpCode,
            'response' => substr($response, 0, 500),
        ]);

        return [
            'success' => false,
            'data' => $decodedResponse ?? $response,
            'http_code' => $httpCode,
            'error' => 'HTTP error ' . $httpCode,
        ];
    }

    public function get(string $endpoint, array $params = [], array $headers = []): array
    {
        if (!empty($params)) {
            $endpoint .= '?' . http_build_query($params);
        }
        return $this->request('GET', $endpoint, null, $headers);
    }

    public function post(string $endpoint, array $data, array $headers = []): array
    {
        return $this->request('POST', $endpoint, $data, $headers);
    }

    public function put(string $endpoint, array $data, array $headers = []): array
    {
        return $this->request('PUT', $endpoint, $data, $headers);
    }

    public function patch(string $endpoint, array $data, array $headers = []): array
    {
        return $this->request('PATCH', $endpoint, $data, $headers);
    }

    public function delete(string $endpoint, array $params = [], array $headers = []): array
    {
        if (!empty($params)) {
            $endpoint .= '?' . http_build_query($params);
        }
        return $this->request('DELETE', $endpoint, null, $headers);
    }
}
