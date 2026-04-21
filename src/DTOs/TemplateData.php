<?php

namespace CubeConnect\DTOs;

readonly class TemplateData
{
    public function __construct(
        public string $name,
        public string $language,
        public string $category,
        public string $status,
        public int $paramsCount,
        public ?string $body,
        public ?string $header,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            name:        $data['name'] ?? '',
            language:    $data['language'] ?? '',
            category:    $data['category'] ?? '',
            status:      $data['status'] ?? '',
            paramsCount: (int) ($data['params_count'] ?? 0),
            body:        $data['body'] ?? null,
            header:      $data['header'] ?? null,
        );
    }

    public function isApproved(): bool
    {
        return $this->status === 'APPROVED';
    }

    public function toArray(): array
    {
        return [
            'name'         => $this->name,
            'language'     => $this->language,
            'category'     => $this->category,
            'status'       => $this->status,
            'params_count' => $this->paramsCount,
            'body'         => $this->body,
            'header'       => $this->header,
        ];
    }
}
