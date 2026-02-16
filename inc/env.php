<?php
/**
 * Environment loading and validation
 */

if (defined('ENV_PHP_INCLUDED')) {
    return;
}
define('ENV_PHP_INCLUDED', true);

const ENV_SCHEMA = [
    'DB_PATH' => ['required' => true, 'type' => 'string'],
    'APP_URL' => ['required' => true, 'type' => 'url'],
    'APP_NAME' => ['required' => true, 'type' => 'string'],
    'GOOGLE_MAPS_API_KEY' => ['required' => true, 'type' => 'string'],
    'GOOGLE_CLIENT_ID' => ['required' => false, 'type' => 'string'],
    'GOOGLE_CLIENT_SECRET' => ['required' => false, 'type' => 'string'],
    'PLUNK_SECRET_KEY' => ['required' => false, 'type' => 'string'],
    'PLUNK_PUBLIC_KEY' => ['required' => false, 'type' => 'string'],
    'PLUNK_BASE_URL' => ['required' => false, 'type' => 'url'],
    'PLUNK_WEBHOOK_SECRET' => ['required' => false, 'type' => 'string'],
    'EMAIL_PROVIDER' => ['required' => false, 'type' => 'enum', 'allowed' => ['plunk']],
    'ANTHROPIC_API_KEY' => ['required' => false, 'type' => 'string'],
    'APP_ENV' => ['required' => true, 'type' => 'enum', 'allowed' => ['dev', 'staging', 'prod']],
    'APP_DEBUG' => ['required' => true, 'type' => 'bool'],
];

function envFilePath(): string {
    return __DIR__ . '/../.env';
}

function loadEnvFile(?string $path = null): void {
    static $loaded = false;

    if ($loaded) {
        return;
    }

    $loaded = true;
    $path = $path ?? envFilePath();

    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if ($key === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
            continue;
        }

        if ($value !== '' && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
            $value = substr($value, 1, -1);
        }

        $commentPos = strpos($value, ' #');
        if ($commentPos !== false) {
            $value = substr($value, 0, $commentPos);
            $value = rtrim($value);
        }

        $existing = getenv($key);
        if ($existing !== false && $existing !== '') {
            $_ENV[$key] = $existing;
            continue;
        }

        $_ENV[$key] = $value;
        putenv($key . '=' . $value);
    }
}

function env(string $key, ?string $default = null): ?string {
    $value = $_ENV[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return (string) $value;
}

function envRequire(string $key): string {
    $value = env($key);

    if ($value === null || $value === '') {
        throw new RuntimeException("Missing required environment variable: {$key}");
    }

    return $value;
}

function envBool(string $key, bool $default = false): bool {
    $value = env($key);

    if ($value === null) {
        return $default;
    }

    $parsed = parseBoolString($value);

    if ($parsed === null) {
        throw new RuntimeException("Invalid boolean value for {$key}: {$value}");
    }

    return $parsed;
}

function parseBoolString(string $value): ?bool {
    $normalized = strtolower(trim($value));

    return match ($normalized) {
        '1', 'true', 'yes', 'on' => true,
        '0', 'false', 'no', 'off' => false,
        default => null,
    };
}

function appEnv(): string {
    $value = env('APP_ENV', 'prod');
    $value = strtolower(trim($value));

    if ($value === 'production') {
        $value = 'prod';
    }

    return $value;
}

function appDebug(): bool {
    return envBool('APP_DEBUG', false);
}

function validateEnvironment(): void {
    static $validated = false;

    if ($validated) {
        return;
    }

    $errors = [];

    foreach (ENV_SCHEMA as $key => $rules) {
        $value = env($key);

        if (($rules['required'] ?? false) && ($value === null || $value === '')) {
            $errors[] = "{$key} is required";
            continue;
        }

        if ($value === null || $value === '') {
            continue;
        }

        switch ($rules['type'] ?? 'string') {
            case 'url':
                if (filter_var($value, FILTER_VALIDATE_URL) === false) {
                    $errors[] = "{$key} must be a valid URL";
                    continue 2;
                }
                $normalized = rtrim($value, '/');
                $_ENV[$key] = $normalized;
                putenv($key . '=' . $normalized);
                break;

            case 'enum':
                $normalized = strtolower($value);
                if (!in_array($normalized, $rules['allowed'] ?? [], true)) {
                    $allowed = implode(', ', $rules['allowed'] ?? []);
                    $errors[] = "{$key} must be one of: {$allowed}";
                    continue 2;
                }
                $_ENV[$key] = $normalized;
                putenv($key . '=' . $normalized);
                break;

            case 'bool':
                $parsed = parseBoolString($value);
                if ($parsed === null) {
                    $errors[] = "{$key} must be a boolean (true/false/1/0/yes/no)";
                    continue 2;
                }
                $normalized = $parsed ? '1' : '0';
                $_ENV[$key] = $normalized;
                putenv($key . '=' . $normalized);
                break;

            default:
                break;
        }
    }

    $googleClientId = env('GOOGLE_CLIENT_ID');
    $googleClientSecret = env('GOOGLE_CLIENT_SECRET');
    if (($googleClientId === null) !== ($googleClientSecret === null)) {
        $errors[] = 'GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET must both be set to enable Google OAuth';
    }

    if (!empty($errors)) {
        throw new RuntimeException('Environment validation failed: ' . implode('; ', $errors));
    }

    $validated = true;
}
