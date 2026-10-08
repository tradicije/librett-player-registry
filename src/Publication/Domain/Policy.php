<?php

// Copyright (C) 2026 Aleksa Dimitrijević. AGPL-3.0-or-later.
declare(strict_types=1);

namespace LibreTT\PlayerRegistry\Publication\Domain;

use LibreTT\PlayerRegistry\Shared\Domain\HttpsUrl;
use LibreTT\PlayerRegistry\Shared\Domain\PlainText;

final readonly class Policy
{
    public function __construct(
        public string $version,
        public string $purpose,
        public DatasetLicense $license,
        public HttpsUrl $datasetTerms,
        public HttpsUrl $mediaTerms,
        public string $minorPolicy = '',
        public string $documentKey = '',
    ) {
        PlainText::assertValid($version, 64, true);
        PlainText::assertValid($purpose, 1000, true);
        PlainText::assertValid($minorPolicy, 1000, false);
        if ($documentKey !== '' && ($license !== DatasetLicense::Custom || preg_match('/\A[0-9a-f-]{36}\.(?:pdf|txt)\z/', $documentKey) !== 1)) {
            throw new \InvalidArgumentException('Invalid license document reference.');
        }
    }

    /** @return array<string, string> */
    public function publicData(): array
    {
        return ['version' => $this->version, 'purpose' => $this->purpose,
            'dataset_terms_url' => $this->datasetTerms->value, 'media_terms_url' => $this->mediaTerms->value,
            'distribution_scope' => 'public-download'];
    }
}
