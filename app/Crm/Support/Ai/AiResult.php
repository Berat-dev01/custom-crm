<?php

namespace App\Crm\Support\Ai;

class AiResult
{
    private function __construct(
        public readonly bool $ok,
        public readonly ?string $content = null,
        public readonly ?string $message = null,
        public readonly int $status = 200
    ) {}

    public static function success(string $content): self
    {
        return new self(true, $content);
    }

    public static function unavailable(?string $message = null): self
    {
        return new self(false, null, $message ?: trans('crm::messages.ai.not_configured'), 202);
    }

    public static function failure(?string $message = null): self
    {
        return new self(false, null, $message ?: trans('crm::messages.ai.request_failed_retry'), 503);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(string $key = 'content'): array
    {
        return [
            'ok' => $this->ok,
            $key => $this->content,
            'message' => $this->message,
        ];
    }
}
