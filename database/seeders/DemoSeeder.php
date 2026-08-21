<?php

namespace Database\Seeders;

use App\Enums\CredentialStatus;
use App\Models\Address;
use App\Models\Category;
use App\Models\Credential;
use App\Models\Listing;
use App\Models\ListingMedia;
use App\Models\Organization;
use App\Models\User;
use App\Services\Publishing\PublishingGate;
use App\Support\Money;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * A small but realistic demo dataset — the personas from the PRD, with
 * credentials that actually satisfy their categories, so the publishing gate
 * can be seen passing and failing on real data rather than fixtures.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $gate = app(PublishingGate::class);

        // ------------------------------------------------------- admin
        $admin = $this->user('admin@elmarche.tn', '+21670000000', 'Admin', isAdmin: true);

        // ---------------------------------------- Mohamed, input dealer
        $mohamed = $this->user('mohamed@example.tn', '+21698111111', 'Mohamed Ben Salah', governorate: 'beja');
        $this->credential($mohamed, 'cin_identity', 'CIN-0001', null, $admin);
        $this->credential($mohamed, 'moa_seed_authorization', 'MOA-2026-014', now()->addMonths(14), $admin);

        $bleDur = Category::where('slug', 'semences-ble-dur')->first();

        $seed = $this->listing($mohamed, $bleDur, [
            'ar' => 'بذور قمح صلب معتمدة - صنف كريم',
            'fr' => 'Semences blé dur certifiées — variété Karim',
            'en' => 'Certified durum wheat seed — Karim',
        ], priceDinars: 165, unit: 'quintal', stock: 400, attributes: [
            'variety' => 'Karim',
            'certification_class' => 'r1',
            'germination_rate' => 94,
            'purity_rate' => 99,
            'production_year' => 2026,
            'under_exploitation_contract' => false,
        ], lot: 'BD-2026-0741');

        $gate->publish($seed);

        // ------------------------------- Association Nour, women's collective
        $nour = Organization::updateOrCreate(
            ['slug' => 'association-nour'],
            [
                'type' => 'association',
                'legal_name' => 'Association Nour — Kairouan',
                'governorate' => 'kairouan',
                'story' => "Quinze femmes de la région de Kairouan produisent conserves et textiles depuis 2019.",
                'verified_at' => now(),
            ],
        );

        $sonia = $this->user('sonia@example.tn', '+21698222222', 'Sonia Trabelsi', governorate: 'nabeul');
        $nour->members()->updateOrCreate(
            ['user_id' => $sonia->id],
            ['role' => 'owner', 'revenue_share_pct' => 100],
        );

        $this->credential($nour, 'association_statutes', 'JORT-2019-882', null, $admin);
        $this->credential($nour, 'sanitary_authorization', 'SAN-2026-3391', now()->addMonths(9), $admin);

        $distillats = Category::where('slug', 'eau-de-rose')->first();

        $roseWater = $this->listing($nour, $distillats, [
            'ar' => 'ماء الورد البلدي - تقطير تقليدي',
            'fr' => 'Eau de rose artisanale — distillation traditionnelle',
            'en' => 'Artisanal rose water — traditional distillation',
        ], priceDinars: 18.5, unit: 'bottle', stock: 60, attributes: [
            'ingredients' => 'Rosa damascena, eau de source',
            'net_weight' => 250,
            'production_date' => now()->subDays(20)->toDateString(),
            'shelf_life_days' => 540,
            'storage' => 'À l\'abri de la lumière',
            'distillation_method' => 'Alambic en cuivre',
        ], lot: 'ROSE-2026-05', seasonal: ['2026-04-01', '2026-06-15']);

        $roseWater->greenAttributes()->sync(['handmade', 'women_led', 'association_made', 'locally_sourced']);
        $gate->publish($roseWater);

        // --------------------------------- a listing that must NOT publish
        // Same association, same category — but no lot number. The gate
        // should refuse it, and it stays visible in the admin queue as an
        // example of the enforcement working.
        $blocked = $this->listing($nour, $distillats, [
            'fr' => 'Eau de fleur d\'oranger (lot manquant)',
            'ar' => 'ماء الزهر (بدون رقم حصة)',
            'en' => 'Orange blossom water (missing lot)',
        ], priceDinars: 16, unit: 'bottle', stock: 20, attributes: [
            'ingredients' => 'Citrus aurantium, eau de source',
            'net_weight' => 250,
            'production_date' => now()->subDays(10)->toDateString(),
            'shelf_life_days' => 365,
        ], lot: null);

        $result = $gate->publish($blocked);

        if ($result->fails()) {
            $this->command?->warn('  Demo: one listing correctly blocked — '.implode('; ', $result->messages()));
        }

        // ----------------------------------------- Ferme El Amal, experiences
        $ferme = $this->user('ferme@example.tn', '+21698333333', 'Ferme El Amal', governorate: 'zaghouan');
        $this->credential($ferme, 'cin_identity', 'CIN-0003', null, $admin);
        $this->credential($ferme, 'liability_insurance', 'ASSUR-2026-7712', now()->addMonths(7), $admin);

        $ateliers = Category::where('slug', 'ateliers-sorties')->first();

        $sortie = $this->listing($ferme, $ateliers, [
            'ar' => 'خرجة مدرسية إلى المزرعة البيداغوجية',
            'fr' => 'Sortie nature — ferme pédagogique',
            'en' => 'Nature outing — educational farm',
        ], priceDinars: 12, unit: 'person', stock: 0, attributes: [
            'capacity_min' => 15,
            'capacity_max' => 45,
            'age_min' => 6,
            'age_max' => 12,
            'supervision_ratio' => '1 adulte / 8 enfants',
            'animation_language' => 'ar',
            'learning_objectives' => "Cycle de l'eau, compostage, découverte des cultures de saison.",
            'accessibility' => 'Accès car, sanitaires, zones ombragées',
            'weather_policy' => 'Report sans frais si alerte météo annoncée 48h avant.',
        ]);

        $gate->publish($sortie);

        // ------------------------------------------------------ a buyer
        $nadia = $this->user('nadia@example.tn', '+21698444444', 'Nadia Gharbi', governorate: 'tunis');

        Address::updateOrCreate(
            ['user_id' => $nadia->id, 'label' => 'Domicile'],
            [
                'contact_name' => 'Nadia Gharbi',
                'contact_phone' => '+21698444444',
                'governorate' => 'tunis',
                'delegation' => 'La Marsa',
                'street' => '12 rue des Oliviers',
                'is_default' => true,
            ],
        );

        $this->command?->info('  Demo data seeded. Admin: admin@elmarche.tn / password');
    }

    private function user(
        string $email,
        string $phone,
        string $name,
        bool $isAdmin = false,
        ?string $governorate = null,
    ): User {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'phone' => $phone,
                'full_name' => $name,
                'password' => Hash::make('password'),
                'phone_verified_at' => now(),
                'cin_verified_at' => now(),
                'is_admin' => $isAdmin,
                'preferred_locale' => 'fr',
                'slug' => \Illuminate\Support\Str::slug($name),
            ],
        );

        $user->grantRole($isAdmin ? 'admin' : 'seller');
        $user->grantRole('buyer');

        if ($governorate) {
            Address::updateOrCreate(
                ['user_id' => $user->id, 'label' => 'Principale'],
                [
                    'contact_name' => $name,
                    'contact_phone' => $phone,
                    'governorate' => $governorate,
                    'is_default' => true,
                ],
            );
        }

        return $user;
    }

    private function credential(
        User|Organization $holder,
        string $type,
        string $number,
        $expires,
        User $admin,
    ): Credential {
        $key = $holder instanceof Organization
            ? ['organization_id' => $holder->id, 'credential_type_code' => $type]
            : ['user_id' => $holder->id, 'credential_type_code' => $type];

        return Credential::updateOrCreate($key, [
            'number' => $number,
            'expires_at' => $expires,
            'status' => CredentialStatus::Approved,
            'verified_by_admin_id' => $admin->id,
            'verified_at' => now(),
        ]);
    }

    private function listing(
        User|Organization $seller,
        ?Category $category,
        array $title,
        float $priceDinars,
        string $unit,
        int $stock,
        array $attributes = [],
        ?string $lot = null,
        ?array $seasonal = null,
    ): Listing {
        $listing = Listing::updateOrCreate(
            ['slug' => \Illuminate\Support\Str::slug($title['fr'] ?? reset($title))],
            [
                'seller_user_id' => $seller instanceof User ? $seller->id : null,
                'seller_org_id' => $seller instanceof Organization ? $seller->id : null,
                'category_id' => $category?->id,
                'title' => $title,
                'price' => Money::fromDinars($priceDinars),
                'unit' => $unit,
                'stock' => $stock,
                'availability_model' => $seasonal ? 'seasonal' : 'in_stock',
                'season_start' => $seasonal[0] ?? null,
                'season_end' => $seasonal[1] ?? null,
                'lot_number' => $lot,
                'attribute_values' => $attributes,
                'governorate' => $seller->governorate ?? null,
            ],
        );

        // The gate requires at least one image; seed a placeholder path so the
        // demo data can actually reach "active".
        if ($listing->media()->count() === 0) {
            ListingMedia::create([
                'listing_id' => $listing->id,
                'path' => 'demo/placeholder.jpg',
                'position' => 0,
            ]);
        }

        return $listing->fresh();
    }
}
