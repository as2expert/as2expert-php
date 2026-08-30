# as2expert (PHP)

Official PHP client for the **AS2Expert** REST API — send and receive AS2/EDI
messages, manage trading partners, certificates and stations, drive Business
Documents, and validate/convert **EDIFACT**.

- PHP 8.1+, `ext-curl` only; no framework required.
- Typed exception hierarchy, automatic retries on `429`/`5xx`, HMAC webhook
  verification.
- Configurable host: `free`, `b2b`, or any self-hosted deployment.

```bash
composer require as2expert/as2expert
```

## Quick start

```php
use As2Expert\Client;

$client = new Client(['token' => 'YOUR_TOKEN', 'environment' => 'free']);
// or: new Client(['token' => '...', 'base_url' => 'https://your-host/api/v1']);

// Send an EDI file to a partner (content is base64-encoded for you)
$client->messages->send('140', 'Order 4711', 'order.edi', file_get_contents('order.edi'));

// List and download inbound messages
foreach ($client->messages->list(['limit' => 20]) as $msg) {
    echo $msg['id'], ' ', $msg['asunto'] ?? '', "\n";
    $bytes = $client->messages->download($msg['id']);   // raw string
}
```

Every method maps to a single POST call (the API is POST-only). Item methods
return an associative array; list methods return a list of arrays.

## EDIFACT

```php
// Parse + validate + translate to JSON ("xml" / "text" also supported)
$out = $client->edifact->convert($rawEdi, 'json');
echo $out['filename'], ' ', $out['content'];

// Build a functional acknowledgement (CONTRL / APERAK)
$ack = $client->edifact->acknowledge($rawEdi);
echo $ack['kind'], ' ', $ack['control_reference'];
```

## Errors

Every call throws a subclass of `As2Expert\ApiException` on failure. Each carries
`$status`, `$errorCode`, and `$payload`:

| Exception | When |
|-----------|------|
| `AuthException` | `401` / `403` |
| `ValidationException` | `400` / `422` (see `->fields`) |
| `NotFoundException` | `404` |
| `RateLimitException` | `429` (see `->retryAfter`) |
| `ServerException` | `5xx` |
| `TransportException` | network/timeout, no HTTP status |

```php
use As2Expert\ValidationException;
use As2Expert\ApiException;

try {
    $client->businessDocuments->create($doc);
} catch (ValidationException $e) {
    echo 'bad document: ', json_encode($e->fields);
} catch (ApiException $e) {
    echo $e->status, ' ', $e->getMessage();
}
```

## Webhooks

AS2Expert signs deliveries with HMAC-SHA256 over `"<timestamp>.<body>"`, sent in
`X-AS2Expert-Timestamp` and `X-AS2Expert-Signature: sha256=<hex>`:

```php
use As2Expert\Webhooks;

$ok = Webhooks::verify(
    $secret,
    $_SERVER['HTTP_X_AS2EXPERT_TIMESTAMP'],
    file_get_contents('php://input'),           // the exact raw body
    $_SERVER['HTTP_X_AS2EXPERT_SIGNATURE'],
);
if (!$ok) {
    http_response_code(400);
    exit;
}
```

## Configuration

`new Client($config)` accepts:

- `token` (required)
- `base_url` **or** `environment` (`"free"` / `"b2b"`)
- `timeout` (seconds, default 30), `max_retries` (default 2), `verify_tls`
  (default true), `user_agent`
- `transport` — inject a custom `As2Expert\Transport` (used in tests)

## Development

```bash
php tests/run.php          # standalone tests, no Composer/network
composer install && ./vendor/bin/phpunit    # PHPUnit suite
AS2EXPERT_TOKEN=... php examples/smoke.php   # E2E against free
```

## License

Apache-2.0 — see [LICENSE](LICENSE).
