<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\DB;

/**
 * FR-088 / LC-09 — gapless, sequential, per-year document numbering.
 *
 * Tunisian invoicing requires an unbroken sequence. Two concurrent checkouts
 * must not be able to claim the same number, so the read and the write happen
 * inside one transaction with a row lock on the previous maximum. Slower than
 * an auto-increment; correct in a way an auto-increment is not, because
 * deleted or rolled-back rows must not leave holes.
 */
class DocumentNumberGenerator
{
    private const PREFIXES = [
        'facture' => 'FAC',
        'devis' => 'DEV',
        'bon_commande' => 'BC',
        'bon_livraison' => 'BL',
    ];

    public function next(string $type, ?int $year = null): array
    {
        $year ??= (int) now()->format('Y');

        return DB::transaction(function () use ($type, $year) {
            $last = Document::query()
                ->where('type', $type)
                ->where('year', $year)
                ->lockForUpdate()
                ->max('sequence');

            $sequence = ((int) $last) + 1;
            $prefix = self::PREFIXES[$type] ?? strtoupper(substr($type, 0, 3));

            return [
                'sequence' => $sequence,
                'year' => $year,
                'number' => sprintf('%s-%d-%06d', $prefix, $year, $sequence),
            ];
        });
    }
}
