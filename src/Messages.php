<?php

declare(strict_types=1);

namespace As2Expert;

final class Messages extends AbstractResource
{
    /**
     * @param array<string,mixed> $filter e.g. ['station' => 1, 'limit' => 20]
     * @return list<array<string,mixed>>
     */
    public function list(array $filter = []): array
    {
        return $this->collection('/messages', $filter);
    }

    /**
     * List a station's folders (id, name, parent_id, count, icono, …).
     * @param array<string,mixed> $filter may carry 'station'
     * @return list<array<string,mixed>>
     */
    public function folders(array $filter = []): array
    {
        return $this->collection('/messages/folders', $filter);
    }

    /** @return array<string,mixed> */
    public function get(mixed $id): array
    {
        return $this->object('/messages/detail', ['id' => $id]);
    }

    /** Return the raw message payload bytes (base64-decoded). */
    public function download(mixed $id): string
    {
        $data = $this->object('/messages/download', ['id' => $id]);
        return self::decodeB64($data);
    }

    /**
     * Send a file to a partner. $content is base64-encoded for you.
     * @return array<string,mixed>
     */
    public function send(mixed $partner, string $subject, string $fileName, string $content): array
    {
        return $this->object('/messages/send', [
            'partner' => $partner,
            'subject' => $subject,
            'file_name' => $fileName,
            'file_content' => base64_encode($content),
        ]);
    }

    /** @return array<string,mixed> */
    public function markRead(mixed $id): array
    {
        return $this->object('/messages/mark-read', ['id' => $id]);
    }

    /** @return array<string,mixed> */
    public function markUnread(mixed $id): array
    {
        return $this->object('/messages/mark-unread', ['id' => $id]);
    }

    /** @return array<string,mixed> */
    public function move(mixed $id, mixed $folder): array
    {
        return $this->object('/messages/move', ['id' => $id, 'folder' => $folder]);
    }

    /** @return array<string,mixed> */
    public function delete(mixed $id): array
    {
        return $this->object('/messages/delete', ['id' => $id]);
    }

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    public function changes(array $params = []): array
    {
        return $this->object('/messages/changes', $params);
    }

    /** @param array<string,mixed> $data */
    private static function decodeB64(array $data): string
    {
        $b64 = '';
        foreach (['content_b64', 'contenido_base64'] as $k) {
            if (isset($data[$k]) && is_string($data[$k]) && $data[$k] !== '') {
                $b64 = $data[$k];
                break;
            }
        }
        if ($b64 === '') {
            return '';
        }
        $decoded = base64_decode($b64, true);
        if ($decoded === false) {
            throw new TransportException('bad base64 in response');
        }
        return $decoded;
    }
}
