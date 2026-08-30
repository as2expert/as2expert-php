<?php

declare(strict_types=1);

// Standalone test runner (no Composer needed): registers a PSR-4 autoloader for
// the As2Expert namespace and exercises the client against a fake transport.

spl_autoload_register(static function (string $class): void {
    $prefix = 'As2Expert\\';
    if (str_starts_with($class, $prefix)) {
        $rel = str_replace('\\', '/', substr($class, strlen($prefix)));
        $file = __DIR__ . '/../src/' . $rel . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

use As2Expert\Client;
use As2Expert\Transport;
use As2Expert\AuthException;
use As2Expert\ValidationException;
use As2Expert\Webhooks;

$failures = 0;
function check(bool $cond, string $label): void
{
    global $failures;
    if ($cond) {
        echo "ok   $label\n";
    } else {
        $failures++;
        echo "FAIL $label\n";
    }
}

// --- Simpler fake: response queue keyed by call order ------------------------
final class Fake implements Transport
{
    /** @var list<array{path:string,body:array<string,mixed>,headers:array<string,string>}> */
    public array $calls = [];
    /** @var list<mixed> */
    public array $responses = [];
    /** @var list<?\Throwable> */
    public array $throws = [];

    public function post(string $path, array $body, array $headers = []): mixed
    {
        $this->calls[] = ['path' => $path, 'body' => $body, 'headers' => $headers];
        $throw = array_shift($this->throws);
        $resp = array_shift($this->responses);
        if ($throw !== null) {
            throw $throw;
        }
        return $resp;
    }

    public function baseUrl(): string { return 'https://fake/api/v1'; }
}

// send(): base64 + path + body
$f = new Fake();
$f->responses = [['message_id' => 'M-1']];
$f->throws = [null];
$client = new Client(['transport' => $f]);
$out = $client->messages->send('140', 'Order', 'o.edi', 'UNB+...');
check(($out['message_id'] ?? null) === 'M-1', 'send returns data');
check($f->calls[0]['path'] === '/messages/send', 'send hits /messages/send');
check($f->calls[0]['body']['file_content'] === base64_encode('UNB+...'), 'send base64-encodes content');
check($f->calls[0]['body']['partner'] === '140', 'send passes partner');

// list(): returns array
$f = new Fake();
$f->responses = [[['id' => 1], ['id' => 2]]];
$f->throws = [null];
$client = new Client(['transport' => $f]);
$msgs = $client->messages->list(['limit' => 10]);
check(count($msgs) === 2 && $msgs[0]['id'] === 1, 'list returns data array');
check($f->calls[0]['body']['limit'] === 10, 'list passes filter');

// download(): base64 decode
$f = new Fake();
$f->responses = [['content_b64' => base64_encode('HELLO')]];
$f->throws = [null];
$client = new Client(['transport' => $f]);
check($client->messages->download(7) === 'HELLO', 'download decodes base64');

// edifact convert body
$f = new Fake();
$f->responses = [['format' => 'json', 'content' => '{}']];
$f->throws = [null];
$client = new Client(['transport' => $f]);
$out = $client->edifact->convert('UNB...', 'json');
check(($out['format'] ?? null) === 'json', 'convert returns data');
check($f->calls[0]['body'] === ['edifact' => 'UNB...', 'format' => 'json', 'sequence' => 1], 'convert body shape');

// business document idempotency header
$f = new Fake();
$f->responses = [['business_document_id' => 'BD-9']];
$f->throws = [null];
$client = new Client(['transport' => $f]);
$client->businessDocuments->create(['type' => 'purchase_order'], 'key-123');
check($f->calls[0]['headers']['Idempotency-Key'] === 'key-123', 'BD create sends Idempotency-Key');

// error mapping via CurlTransport::errorForStatus
$f = new Fake();
$f->responses = [null];
$f->throws = [new AuthException('bad token', 401)];
$client = new Client(['transport' => $f]);
try {
    $client->partners->list();
    check(false, 'auth error thrown');
} catch (AuthException $e) {
    check($e->status === 401 && str_contains($e->getMessage(), 'bad token'), 'auth error thrown');
}

$err = \As2Expert\CurlTransport::errorForStatus(422, 'invalid', ['fields' => [['path' => 'partner']]]);
check($err instanceof ValidationException && $err->fields[0]['path'] === 'partner', 'validation error carries fields');

// webhook signature roundtrip
$sig = Webhooks::sign('secret-0123456789abcdef', '1000', '{"a":1}');
check(str_starts_with($sig, 'sha256='), 'sign returns sha256= prefix');
check(Webhooks::verify('secret-0123456789abcdef', '1000', '{"a":1}', $sig, 300, 1000), 'verify accepts valid');
check(!Webhooks::verify('secret-0123456789abcdef', '1000', '{"a":1}', $sig, 300, 99999), 'verify rejects stale');
check(!Webhooks::verify('secret-0123456789abcdef', '1000', '{"a":2}', $sig, 300, 1000), 'verify rejects tamper');
check(!Webhooks::verify('secret-0123456789abcdef', 'nope', '{"a":1}', $sig, 300, 1000), 'verify rejects bad ts');

// environment preset
$client = new Client(['token' => 't', 'environment' => 'free']);
check($client->baseUrl() === 'https://free.as2expert.com/api/v1', 'environment preset');

echo "\n" . ($failures === 0 ? "all PHP tests passed\n" : "$failures FAILED\n");
exit($failures === 0 ? 0 : 1);
