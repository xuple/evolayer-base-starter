<?php

namespace App\Support\EvoLayer;

use DateTimeInterface;
use Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator;

class StarterMediaUrlGenerator extends DefaultUrlGenerator
{
    public function getUrl(): string
    {
        if ($this->isContactAttachment()) {
            return route('evolayer.starter.contact-attachments.show', $this->media);
        }

        return parent::getUrl();
    }

    public function getTemporaryUrl(DateTimeInterface $expiration, array $options = []): string
    {
        if ($this->isContactAttachment()) {
            return $this->getUrl();
        }

        return parent::getTemporaryUrl($expiration, $options);
    }

    private function isContactAttachment(): bool
    {
        return $this->conversion === null
            && $this->media?->collection_name === 'attachments';
    }
}
