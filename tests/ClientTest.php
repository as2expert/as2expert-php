<?php

declare(strict_types=1);

namespace As2Expert\Tests;

use As2Expert\Client;
use As2Expert\Transport;
use As2Expert\AuthException;
use As2Expert\CurlTransport;
use As2Expert\ValidationException;
use As2Expert\Webhooks;
use PHPUnit\Framework\TestCase;

/** In-memory transport for tests. */
final class FakeTransport implements Transport
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

    public function baseUrl(): string
    {
        return 'https://fake/api/v1';
    }
}

final class ClientTest extends TestCase
{
    private function client(FakeTransport $t): Client
    {
        return new Client(['transport' => $t]);
    }

    public function testSendEncodesBase64AndPath(): void
    {
        $t = new FakeTransport();
        $t->responses = [['message_id' => 'M-1']];
        $t->throws = [null];
        $out = $this->client($t)->messages->send('140', 'Order', 'o.edi', 'UNB+...');
        $this->assertSame('M-1', $out['message_id']);
        $this->assertSame('/messages/send', $t->calls[0]['path']);
        $this->assertSame(base64_encode('UNB+...'), $t->calls[0]['body']['file_content']);
    }

    public function testListReturnsDataArray(): void
    {
        $t = new FakeTransport();
        $t->responses = [[['id' => 1], ['id' => 2]]];
        $t->throws = [null];
        $msgs = $this->client($t)->messages->list(['limit' => 10]);
        $this->assertCount(2, $msgs);
        $this->assertSame(1, $msgs[0]['id']);
    }

    public function testDownloadDecodesBase64(): void
    {
        $t = new FakeTransport();
        $t->responses = [['content_b64' => base64_encode('HELLO')]];
        $t->throws = [null];
        $this->assertSame('HELLO', $this->client($t)->messages->download(7));
    }

    public function testConvertBody(): void
    {
        $t = new FakeTransport();
        $t->responses = [['format' => 'json']];
        $t->throws = [null];
        $this->client($t)->edifact->convert('UNB...', 'json');
        $this->assertSame(
            ['edifact' => 'UNB...', 'format' => 'json', 'sequence' => 1],
            $t->calls[0]['body']
        );
    }

    public function testAuthErrorMapping(): void
    {
        $t = new FakeTransport();
        $t->responses = [null];
        $t->throws = [new AuthException('bad token', 401)];
        $this->expectException(AuthException::class);
        $this->client($t)->partners->list();
    }

    public function testValidationErrorCarriesFields(): void
    {
        $err = CurlTransport::errorForStatus(422, 'invalid', ['fields' => [['path' => 'partner']]]);
        $this->assertInstanceOf(ValidationException::class, $err);
        $this->assertSame('partner', $err->fields[0]['path']);
    }

    public function testWebhookSignatureRoundtrip(): void
    {
        $sig = Webhooks::sign('secret-0123456789abcdef', '1000', '{"a":1}');
        $this->assertStringStartsWith('sha256=', $sig);
        $this->assertTrue(Webhooks::verify('secret-0123456789abcdef', '1000', '{"a":1}', $sig, 300, 1000));
        $this->assertFalse(Webhooks::verify('secret-0123456789abcdef', '1000', '{"a":1}', $sig, 300, 99999));
        $this->assertFalse(Webhooks::verify('secret-0123456789abcdef', '1000', '{"a":2}', $sig, 300, 1000));
    }

    public function testEnvironmentPreset(): void
    {
        $client = new Client(['token' => 't', 'environment' => 'free']);
        $this->assertSame('https://free.as2expert.com/api/v1', $client->baseUrl());
    }
}
