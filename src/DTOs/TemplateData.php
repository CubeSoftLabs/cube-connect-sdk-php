<?php

namespace CubeConnect\DTOs;

readonly class TemplateData
{
    /** Header has no content. */
    public const HEADER_NONE = 'none';

    /** Header carries inline text — see $header. */
    public const HEADER_TEXT = 'text';

    /** Image header — requires a media parameter at send time. */
    public const HEADER_IMAGE = 'image';

    /** Video header — requires a media parameter at send time. */
    public const HEADER_VIDEO = 'video';

    /** Document header — requires a media parameter at send time. */
    public const HEADER_DOCUMENT = 'document';

    public function __construct(
        public string $name,
        public string $language,
        public string $category,
        public string $status,
        public int $paramsCount,
        public ?string $body,
        /** Header text — populated only when $headerType === 'text'. */
        public ?string $header,
        /** Always present. One of self::HEADER_*. Tells the caller whether
         *  (and what kind of) media must be sent with the message. */
        public string $headerType,
        /** Sample media URL Meta has on file (set at template creation).
         *  Populated only when $headerType is image/video/document. Callers
         *  may reuse this URL on send, or pass their own media via the
         *  `header` component parameter. */
        public ?string $headerSampleMediaUrl,
        public ?string $footer,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            name:                 $data['name'] ?? '',
            language:             $data['language'] ?? '',
            category:             $data['category'] ?? '',
            status:               $data['status'] ?? '',
            paramsCount:          (int) ($data['params_count'] ?? 0),
            body:                 $data['body'] ?? null,
            header:               $data['header'] ?? null,
            headerType:           strtolower((string) ($data['header_type'] ?? self::HEADER_NONE)),
            headerSampleMediaUrl: $data['header_sample_media_url'] ?? null,
            footer:               $data['footer'] ?? null,
        );
    }

    public function isApproved(): bool
    {
        return $this->status === 'APPROVED';
    }

    /** True when the template needs a media parameter (image/video/document)
     *  to be supplied in the `components.header.parameters` array on send. */
    public function requiresHeaderMedia(): bool
    {
        return in_array($this->headerType, [self::HEADER_IMAGE, self::HEADER_VIDEO, self::HEADER_DOCUMENT], true);
    }

    public function toArray(): array
    {
        return [
            'name'                    => $this->name,
            'language'                => $this->language,
            'category'                => $this->category,
            'status'                  => $this->status,
            'params_count'            => $this->paramsCount,
            'body'                    => $this->body,
            'header'                  => $this->header,
            'header_type'             => $this->headerType,
            'header_sample_media_url' => $this->headerSampleMediaUrl,
            'footer'                  => $this->footer,
        ];
    }
}
