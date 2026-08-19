<?php

namespace App\Services\Publishing;

/**
 * A single reason a listing may not go live.
 *
 * Violations carry a machine code as well as a human message, because the
 * codes are what get emitted as `listing_blocked_by_requirement` analytics
 * events — the most under-rated metric in the PRD. They tell you exactly
 * where onboarding friction is killing your supply.
 */
final class Violation
{
    public function __construct(
        public readonly string $code,
        public readonly string $message,
        public readonly array $context = [],
        public readonly bool $fixableBySeller = true,
    ) {}

    public static function missingCredential(string $credentialCode, string $credentialName): self
    {
        return new self(
            code: 'missing_credential',
            message: __('publishing.missing_credential', ['credential' => $credentialName]),
            context: ['credential_type' => $credentialCode],
        );
    }

    public static function expiredCredential(string $credentialCode, string $credentialName): self
    {
        return new self(
            code: 'expired_credential',
            message: __('publishing.expired_credential', ['credential' => $credentialName]),
            context: ['credential_type' => $credentialCode],
        );
    }

    public static function priceAboveCap(int $price, int $ceiling, string $unit): self
    {
        return new self(
            code: 'price_above_cap',
            message: __('publishing.price_above_cap', [
                'ceiling' => \App\Support\Money::format($ceiling),
                'unit' => $unit,
            ]),
            context: ['price' => $price, 'ceiling' => $ceiling],
        );
    }

    public static function missingField(string $key, string $label): self
    {
        return new self(
            code: 'missing_required_field',
            message: __('publishing.missing_field', ['field' => $label]),
            context: ['field' => $key],
        );
    }

    public static function missingLotNumber(): self
    {
        return new self(
            code: 'missing_lot_number',
            message: __('publishing.missing_lot_number'),
        );
    }

    public static function invasiveSpecies(string $botanicalName): self
    {
        return new self(
            code: 'invasive_species',
            message: __('publishing.invasive_species', ['species' => $botanicalName]),
            context: ['species' => $botanicalName],
            fixableBySeller: false,
        );
    }

    public static function missingSeasonWindow(): self
    {
        return new self(
            code: 'missing_season_window',
            message: __('publishing.missing_season_window'),
        );
    }

    public static function missingMedia(): self
    {
        return new self(
            code: 'missing_media',
            message: __('publishing.missing_media'),
        );
    }

    public static function missingTitle(): self
    {
        return new self(
            code: 'missing_title',
            message: __('publishing.missing_title'),
        );
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'context' => $this->context,
        ];
    }
}
