<?php

namespace Database\Seeders;

use App\Models\AgreementVersion;
use App\Models\Carrier;
use App\Models\DeliveryZone;
use App\Models\Species;
use Illuminate\Database\Seeder;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        // Delivery — one partner at launch, flat rate per governorate.
        $carrier = Carrier::updateOrCreate(
            ['code' => 'partner'],
            ['name' => 'Delivery partner', 'supports_cod' => true, 'is_active' => true],
        );

        // Coastal and Tunis-adjacent governorates are next-day; the interior
        // and the south take longer, which matters because perishables are
        // hidden from any zone slower than their category allows.
        $fast = ['tunis', 'ariana', 'ben_arous', 'manouba', 'nabeul', 'bizerte', 'zaghouan'];
        $medium = ['sousse', 'monastir', 'mahdia', 'sfax', 'beja', 'jendouba', 'kef', 'siliana', 'kairouan'];

        foreach (config('marketplace.governorates') as $governorate) {
            $days = match (true) {
                in_array($governorate, $fast, true) => 1,
                in_array($governorate, $medium, true) => 2,
                default => 3,
            };

            DeliveryZone::updateOrCreate(
                ['carrier_code' => $carrier->code, 'governorate' => $governorate],
                [
                    'base_price' => match ($days) { 1 => 7_000, 2 => 9_000, default => 12_000 },
                    'per_kg' => 500,
                    'lead_time_days' => $days,
                    'cod_supported' => true,
                ],
            );
        }

        // FR-037 — species registry. A small starter set: two natives worth
        // promoting, and two species widely regarded as invasive in North
        // Africa, blocked from listing entirely.
        $species = [
            ['Olea europaea', ['fr' => 'Olivier', 'ar' => 'زيتون', 'en' => 'Olive'], true, false, 'none', 'low'],
            ['Ceratonia siliqua', ['fr' => 'Caroubier', 'ar' => 'خروب', 'en' => 'Carob'], true, false, 'none', 'low'],
            ['Rosa damascena', ['fr' => 'Rosier de Damas', 'ar' => 'ورد دمشقي', 'en' => 'Damask rose'], false, false, 'none', 'medium'],
            ['Nerium oleander', ['fr' => 'Laurier-rose', 'ar' => 'دفلة', 'en' => 'Oleander'], true, false, 'toxic', 'low'],
            ['Acacia saligna', ['fr' => 'Acacia cyanophylla', 'ar' => 'أكاسيا', 'en' => 'Golden wreath wattle'], false, true, 'none', 'low'],
            ['Carpobrotus edulis', ['fr' => 'Griffe de sorcière', 'ar' => 'مخلب الساحرة', 'en' => 'Hottentot fig'], false, true, 'none', 'low'],
        ];

        foreach ($species as [$botanical, $common, $native, $blocked, $toxicity, $water]) {
            Species::updateOrCreate(
                ['botanical_name' => $botanical],
                [
                    'common_name' => $common,
                    'is_native' => $native,
                    'is_invasive_blocked' => $blocked,
                    'toxicity_level' => $toxicity,
                    'water_need' => $water,
                ],
            );
        }

        // FR-103 — versioned agreements. Replace the placeholder text with
        // what your lawyer drafts; the versioning machinery is what matters.
        foreach ([['buyer', 'Conditions générales de vente'], ['seller', 'Contrat vendeur']] as [$type, $title]) {
            AgreementVersion::updateOrCreate(
                ['type' => $type, 'version' => '1.0'],
                [
                    'title' => ['fr' => $title, 'ar' => $title, 'en' => $title],
                    'effective_from' => now()->toDateString(),
                ],
            );
        }
    }
}
