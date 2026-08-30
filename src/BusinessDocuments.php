<?php

declare(strict_types=1);

namespace As2Expert;

final class BusinessDocuments extends AbstractResource
{
    /**
     * Create a business document. Pass a non-empty $idempotencyKey to make
     * retries safe.
     *
     * @param array<string,mixed> $document
     * @return array<string,mixed>
     */
    public function create(array $document, string $idempotencyKey = ''): array
    {
        $headers = $idempotencyKey !== '' ? ['Idempotency-Key' => $idempotencyKey] : [];
        return $this->object('/business-documents', $document, $headers);
    }

    /** @return array<string,mixed> */
    public function get(mixed $businessDocumentId): array
    {
        return $this->object('/business-documents/detail', [
            'business_document_id' => $businessDocumentId,
        ]);
    }

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    public function diagnostics(array $params = []): array
    {
        return $this->object('/business-documents/diagnostics', $params);
    }
}
