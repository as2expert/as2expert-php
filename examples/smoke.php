<?php

declare(strict_types=1);

// E2E smoke test: AS2EXPERT_TOKEN=... php examples/smoke.php

spl_autoload_register(static function (string $class): void {
    $prefix = 'As2Expert\\';
    if (str_starts_with($class, $prefix)) {
        $file = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

use As2Expert\Client;
use As2Expert\ApiException;

$token = getenv('AS2EXPERT_TOKEN');
if ($token === false || $token === '') {
    fwrite(STDERR, "set AS2EXPERT_TOKEN\n");
    exit(2);
}

try {
    $client = new Client(['token' => $token, 'environment' => 'free']);
    echo 'base_url: ' . $client->baseUrl() . "\n";

    $edi = "UNB+UNOC:3+A+B+260830:1000+1'UNH+1+ORDERS:D:96A:UN'BGM+220+PO-PHP'UNT+2+1'UNZ+1+1'";
    $out = $client->edifact->convert($edi, 'json');
    echo 'convert -> filename=' . ($out['filename'] ?? '') .
        ' content_len=' . strlen((string) ($out['content'] ?? '')) . "\n";

    $ack = $client->edifact->acknowledge(
        "UNA:+.?*'UNB+UNOC:3+A:14+B:14+260830:1000+X9'UNH+M1+ORDERS:D:96A:UN'BGM+220+P'UNT+2+M1'UNZ+1+X9'"
    );
    echo 'acknowledge -> kind=' . ($ack['kind'] ?? '') .
        ' ctrl=' . ($ack['control_reference'] ?? '') . "\n";

    $msgs = $client->messages->list(['limit' => 3]);
    echo 'messages.list -> ' . count($msgs) . " items\n";
} catch (ApiException $e) {
    fwrite(STDERR, 'API error' . ($e->status !== null ? " ({$e->status})" : '') . ': ' . $e->getMessage() . "\n");
    exit(1);
}
