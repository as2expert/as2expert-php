<?php

declare(strict_types=1);

namespace As2Expert;

final class Edifact extends AbstractResource
{
    /** @return array<string,mixed> */
    public function analyze(string $edifact): array
    {
        return $this->object('/edifact/analyze', ['edifact' => $edifact]);
    }

    /**
     * Alias of {@see analyze()} — the same endpoint.
     * @return array<string,mixed>
     */
    public function validate(string $edifact): array
    {
        return $this->analyze($edifact);
    }

    /**
     * Translate an interchange to "json", "xml", or "text".
     * @return array<string,mixed>
     */
    public function convert(string $edifact, string $format = 'json'): array
    {
        return $this->object('/edifact/convert', [
            'edifact' => $edifact,
            'format' => $format,
            'sequence' => 1,
        ]);
    }

    /**
     * Build a functional acknowledgement. $kind is "contrl" or "aperak".
     *
     * @param list<array<string,mixed>> $errors
     * @return array<string,mixed>
     */
    public function acknowledge(string $edifact, string $kind = 'contrl', bool $acknowledged = true, array $errors = []): array
    {
        return $this->object('/edifact/acknowledge', [
            'edifact' => $edifact,
            'kind' => $kind,
            'acknowledged' => $acknowledged,
            'errors' => $errors,
        ]);
    }

    /**
     * Build a minimal valid skeleton; set $compose to also serialize it.
     * @return array<string,mixed>
     */
    public function skeleton(string $messageType, string $release, bool $compose = false): array
    {
        return $this->object('/edifact/skeleton', [
            'message_type' => $messageType,
            'release' => $release,
            'compose' => $compose,
        ]);
    }
}
