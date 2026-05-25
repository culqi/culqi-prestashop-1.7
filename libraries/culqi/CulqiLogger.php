<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class CulqiLogger
{
    private static $instance = null;
    private $source = 'culqi';
    private $logFile;
    private $employeeId;

    private $sensitive_fields = [
        'cardNumber',
        'card_number',
        'cvv',
        'token',
        'authorization',
        'password',
        'secret',
        'rsa_sk_plugin',
        'rsa_pk_culqi',
        'pk',
        'private_key',
        'public_key',
        'culqi_rsa_sk',
        'culqi_rsa_pk',
    ];

    private function __construct()
    {
        $this->logFile = dirname(__FILE__, 3) . '/logs/culqi.json';
        $this->ensureLogDirectory();

        $this->employeeId = null;
        if (Context::getContext()->employee && Context::getContext()->employee->id) {
            $this->employeeId = (int) Context::getContext()->employee->id;
        }
    }

    public static function get_instance(): CulqiLogger
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function debug(string $module, string $message, array $context = []): void
    {
        $this->log('debug', $module, $message, $context);
    }

    public function info(string $module, string $message, array $context = []): void
    {
        $this->log('info', $module, $message, $context);
    }

    public function notice(string $module, string $message, array $context = []): void
    {
        $this->log('notice', $module, $message, $context);
    }

    public function warning(string $module, string $message, array $context = []): void
    {
        $this->log('warning', $module, $message, $context);
    }

    public function error(string $module, string $message, array $context = []): void
    {
        $this->log('error', $module, $message, $context);
    }

    public function critical(string $module, string $message, array $context = []): void
    {
        $this->log('critical', $module, $message, $context);
    }

    public function alert(string $module, string $message, array $context = []): void
    {
        $this->log('alert', $module, $message, $context);
    }

    public function emergency(string $module, string $message, array $context = []): void
    {
        $this->log('emergency', $module, $message, $context);
    }

    private function log(string $level, string $module, string $message, array $context = []): void
    {
        if (!$this->should_log($level)) {
            return;
        }

        $formatted_context = $this->format_context($context);
        $formatted_message = $this->format_message($module, $message, $formatted_context);
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');

        $entry = [
            'timestamp' => $timestamp,
            'level' => $level,
            'module' => $module,
            'message' => $message,
            'context' => $formatted_context,
            'source' => $this->source,
            'employee_id' => $this->employeeId,
        ];

        $this->write_log($entry);

        $psLevel = $this->get_prestashop_level($level);
        PrestaShopLogger::addLog($formatted_message, $psLevel, null, null, null, true, $this->employeeId);

        switch ($level) {
            case 'debug':
            case 'info':
            case 'notice':
                break;
            case 'warning':
            case 'error':
                PrestaShopLogger::addLog($formatted_message, $psLevel, null, null, null, false, $this->employeeId);
                break;
            default:
                PrestaShopLogger::addLog($formatted_message, 3, null, null, null, false, $this->employeeId);
                break;
        }
    }

    private function should_log(string $level): bool
    {
        if ($level === 'debug') {
            if (!$this->is_debug_active()) {
                return false;
            }
        }

        if (!$this->is_debug_active()) {
            $non_prod_levels = ['debug', 'info', 'notice'];
            if (in_array($level, $non_prod_levels)) {
                return false;
            }
        }

        return true;
    }

    private function is_debug_active(): bool
    {
        if (defined('CULQI_DEBUG') && CULQI_DEBUG) {
            return true;
        }

        $debug_setting = Configuration::get('CULQI_DEBUG');
        return $debug_setting === 'true' || $debug_setting === '1';
    }

    private function get_prestashop_level(string $level): int
    {
        return match($level) {
            'debug' => 0,
            'info', 'notice' => 1,
            'warning' => 2,
            'error' => 3,
            'critical', 'alert', 'emergency' => 4,
            default => 1,
        };
    }

    private function format_context(array $context): array
    {
        return $this->sanitize($context);
    }

    private function sanitize(array $data, int $depth = 0): array
    {
        if ($depth > 10) {
            return ['**MAX_DEPTH**'];
        }

        $sanitized = [];
        foreach ($data as $key => $value) {
            if ($this->is_sensitive_key($key)) {
                $sanitized[$key] = '***MASKED***';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitize($value, $depth + 1);
            } elseif (is_string($value) && $this->looks_like_sensitive_string($value)) {
                $sanitized[$key] = $this->mask_string($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    private function is_sensitive_key(string $key): bool
    {
        $lower_key = strtolower($key);
        foreach ($this->sensitive_fields as $field) {
            if (strpos($lower_key, strtolower($field)) !== false) {
                return true;
            }
        }
        return false;
    }

    private function looks_like_sensitive_string(string $value): bool
    {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }
        if (strlen($value) > 50 && strpos($value, '.') !== false) {
            return true;
        }
        if (preg_match('/^[A-Za-z0-9\=\-_]{50,}$/', $value)) {
            return true;
        }
        return false;
    }

    private function mask_string(string $value): string
    {
        if (strlen($value) <= 8) {
            return '***';
        }
        return substr($value, 0, 8) . '***';
    }

    private function format_message(string $module, string $message, array $context): string
    {
        $prefix = '[CULQI][' . $module . '] ' . $message;
        if (empty($context)) {
            return $prefix;
        }

        $encoded = json_encode($context);
        if ($encoded === false) {
            return $prefix . ' | Context: [JSON_ENCODE_FAILED]';
        }

        return $prefix . ' | Context: ' . $encoded;
    }

    private function ensureLogDirectory(): void
    {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    private function write_log(array $entry): void
    {
        $this->rotate();

        $line = json_encode($entry) . "\n";

        if (@file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX) === false) {
            $fallbackFile = $this->getFallbackLogFile();
            @file_put_contents($fallbackFile, $line, FILE_APPEND | LOCK_EX);
        }
    }

    private function getFallbackLogFile(): string
    {
        return sys_get_temp_dir() . '/culqi_' . md5(_PS_VERSION_) . '.json';
    }

    private function rotate(): void
    {
        if (!file_exists($this->logFile)) {
            return;
        }

        $maxAge = 30 * 24 * 60 * 60;
        $now = time();
        $lines = [];

        $handle = fopen($this->logFile, 'r');
        if (!$handle) {
            return;
        }

        while (($line = fgets($handle)) !== false) {
            $lines[] = $line;
        }
        fclose($handle);

        $filtered = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $entry = json_decode($line, true);
            if (!$entry || !isset($entry['timestamp'])) {
                continue;
            }

            $timestamp = strtotime($entry['timestamp']);
            if ($timestamp && ($now - $timestamp) > $maxAge) {
                continue;
            }

            $filtered[] = $line;
        }

        file_put_contents($this->logFile, implode("\n", $filtered) . "\n", LOCK_EX);
    }

    public function set_source(string $source): void
    {
        $this->source = $source;
    }
}
