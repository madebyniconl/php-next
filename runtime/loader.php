<?php
declare(strict_types=1);
error_reporting(E_ALL & ~E_DEPRECATED);   

if (!extension_loaded('ffi')) {
    throw new RuntimeException('FFI extension required');
}

const ULTRA2_MAGIC = 'ULTRA2';

final class Ultra2PayloadRegistry
{
    /** @var array<string,string> */
    private static $payloads = [];

    public static function store(string $payload): string
    {
        $id = bin2hex(random_bytes(8));
        self::$payloads[$id] = $payload;

        return $id;
    }

    public static function take(string $id): ?string
    {
        if (!array_key_exists($id, self::$payloads)) {
            return null;
        }

        $data = self::$payloads[$id];
        unset(self::$payloads[$id]);

        return $data;
    }

    public static function forget(string $id): void
    {
        unset(self::$payloads[$id]);
    }
}

final class Ultra2Stream
{
    private $data = '';
    private $position = 0;

    public function stream_open($path, $mode, $options, &$opened_path): bool
    {
        $id = parse_url($path, PHP_URL_HOST);
        if (!is_string($id) || $id === '') {
            return false;
        }

        $payload = Ultra2PayloadRegistry::take($id);
        if ($payload === null) {
            return false;
        }

        $this->data = $payload;
        $this->position = 0;
        $opened_path = $path;

        return true;
    }

    public function stream_read($count)
    {
        $chunk = substr($this->data, $this->position, $count);
        $this->position += strlen((string) $chunk);

        return $chunk;
    }

    public function stream_eof(): bool
    {
        return $this->position >= strlen($this->data);
    }

    public function stream_stat(): array
    {
        return [
            'size' => strlen($this->data),
        ];
    }

    public function stream_set_option($option, $arg1, $arg2): bool
    {
        return false;
    }
}

function ultra2_seed_path(): string
{
    return __DIR__ . '/../compiler/build_seed';
}

function ultra2_runtime_seed(): string
{
    $seedFile = ultra2_seed_path();
    if (!is_file($seedFile)) {
        throw new RuntimeException('ULTRA2: missing build seed');
    }

    if (decoct(fileperms($seedFile) & 0777) !== '600') {
        throw new RuntimeException('Seed file must be 0600');
    }

    $seed = file_get_contents($seedFile);
    if ($seed === false || strlen($seed) < 32) {
        throw new RuntimeException('ULTRA2: invalid build seed');
    }

    return $seed;
}

function ultra2_parse_container(string $blob): array
{
    $parts = explode("\n", trim($blob), 4);
    if (count($parts) !== 4 || $parts[0] !== ULTRA2_MAGIC) {
        throw new RuntimeException('ULTRA2: invalid container');
    }

    $iv = base64_decode($parts[1], true);
    $hmac = base64_decode($parts[2], true);
    $ciphertext = base64_decode($parts[3], true);

    if ($iv === false || $hmac === false || $ciphertext === false) {
        throw new RuntimeException('ULTRA2: corrupt base64 payload');
    }

    return [$iv, $hmac, $ciphertext];
}

function ultra2_project_id(string $path, ?string $preferred): string
{
    if ($preferred !== null && $preferred !== '') {
        return $preferred;
    }

    $stem = pathinfo($path, PATHINFO_FILENAME);

    return $stem !== '' ? $stem : basename($path);
}

function ultra2_execute_php(string $payload): void
{
    $id = Ultra2PayloadRegistry::store($payload);
    $protocol = 'ultra2' . $id;

    if (!stream_wrapper_register($protocol, Ultra2Stream::class)) {
        Ultra2PayloadRegistry::forget($id);
        throw new RuntimeException('ULTRA2: stream register failed');
    }

    $path = $protocol . '://' . $id;

    try {
        $result = include $path;
        if ($result === false) {
            throw new RuntimeException('ULTRA2: payload execution failed');
        }
    } finally {
        stream_wrapper_unregister($protocol);
        Ultra2PayloadRegistry::forget($id);
    }
}

function ultra2_protect_lib(): FFI
{
    static $lib = null;
    if ($lib !== null) {
        return $lib;
    }

    $so = __DIR__ . '/ffi/protect.so';
    if (!is_file($so)) {
        throw new RuntimeException('ULTRA2: protect.so missing');
    }

    $lib = FFI::cdef('int protect(void* addr, size_t len);', $so);

    return $lib;
}

function load_ultra2(string $path, ?string $projectId = null): void
{
    if (!is_file($path)) {
        throw new RuntimeException('ULTRA2: blob not found');
    }

    $blob = file_get_contents($path);
    if ($blob === false) {
        throw new RuntimeException('ULTRA2: unable to read blob');
    }

    [$iv, $hmac, $ciphertext] = ultra2_parse_container($blob);
    $seed = ultra2_runtime_seed();
    $resolvedId = ultra2_project_id($path, $projectId);
    $key = hash_hmac('sha256', $seed, $resolvedId, true);

    $expected = hash_hmac('sha256', $iv . $ciphertext, $key, true);
    if (!hash_equals($expected, $hmac)) {
        throw new RuntimeException('ULTRA2: HMAC mismatch');
    }

    $plaintext = openssl_decrypt($ciphertext, 'aes-256-ctr', $key, OPENSSL_RAW_DATA, $iv);
    if ($plaintext === false) {
        throw new RuntimeException('ULTRA2: decryption failed');
    }

    $length = strlen($plaintext);
    if ($length === 0) {
        throw new RuntimeException('ULTRA2: empty payload');
    }

    $buffer = FFI::new("unsigned char[$length]", false);
    FFI::memcpy($buffer, $plaintext, $length);

    $lib = ultra2_protect_lib();
    $ptr = FFI::addr($buffer[0]);

    if ($lib->protect($ptr, $length) !== 0) {
        throw new RuntimeException('ULTRA2: mprotect failed');
    }

    ultra2_execute_php($plaintext);
}
