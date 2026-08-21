<?php

namespace Database\Seeders;

use App\Models\GreenScoreRule;
use Illuminate\Database\Seeder;

/**
 * Phase 2 — the default Green Score rule set. Every rule below points at
 * data that already exists (a green attribute, a badge, a governorate) —
 * nothing here invents a signal the platform cannot actually verify.
 * Weights are configurable afterwards from the admin Green Score screen.
 */
class GreenScoreRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'code' => 'local_production',
                'name' => ['ar' => 'إنتاج محلي', 'fr' => 'Production locale', 'en' => 'Local production'],
                'description' => [
                    'ar' => 'الموقع الجغرافي للإنتاج معروف ومسجل.',
                    'fr' => 'Le lieu de production est connu et enregistré.',
                    'en' => 'The production location is known and on file.',
                ],
                'icon' => '🇹🇳', 'category' => 'origin',
                'check_type' => 'origin_governorate', 'check_value' => null,
                'points' => 25,
            ],
            [
                'code' => 'locally_sourced',
                'name' => ['ar' => 'مواد أولية محلية', 'fr' => 'Matières premières locales', 'en' => 'Locally sourced'],
                'description' => [
                    'ar' => 'أعلن البائع أن المواد الأولية مصدرها محلي.',
                    'fr' => "Le vendeur déclare des matières premières d'origine locale.",
                    'en' => 'The seller has tagged this listing as sourced locally.',
                ],
                'icon' => '🌾', 'category' => 'origin',
                'check_type' => 'green_attribute', 'check_value' => 'locally_sourced',
                'points' => 10,
            ],
            [
                'code' => 'organic_certified',
                'name' => ['ar' => 'شهادة بيولوجي', 'fr' => 'Certifié biologique', 'en' => 'Organic certified'],
                'description' => [
                    'ar' => 'المنتج مصنف بيولوجيا معتمدا من قبل البائع.',
                    'fr' => 'Le produit est déclaré certifié biologique par le vendeur.',
                    'en' => 'The listing is tagged organic certified by the seller.',
                ],
                'icon' => '🌱', 'category' => 'certification',
                'check_type' => 'green_attribute', 'check_value' => 'organic_certified',
                'points' => 15,
            ],
            [
                'code' => 'verified_seller',
                'name' => ['ar' => 'بائع موثق', 'fr' => 'Vendeur vérifié', 'en' => 'Verified seller'],
                'description' => [
                    'ar' => 'هوية البائع تم التحقق منها من قبل الإدارة.',
                    'fr' => "L'identité du vendeur a été vérifiée par l'équipe.",
                    'en' => "The seller's identity was reviewed and approved by the marketplace.",
                ],
                'icon' => '✅', 'category' => 'certification',
                'check_type' => 'seller_badge', 'check_value' => 'verified_seller',
                'points' => 10,
            ],
            [
                'code' => 'plastic_free_packaging',
                'name' => ['ar' => 'بدون بلاستيك', 'fr' => 'Sans plastique', 'en' => 'Plastic-free packaging'],
                'description' => [
                    'ar' => 'التغليف بدون بلاستيك حسب تصريح البائع.',
                    'fr' => 'Emballage sans plastique, déclaré par le vendeur.',
                    'en' => 'Packaging is tagged plastic-free by the seller.',
                ],
                'icon' => '♻️', 'category' => 'packaging',
                'check_type' => 'green_attribute', 'check_value' => 'plastic_free',
                'points' => 8,
            ],
            [
                'code' => 'refillable_packaging',
                'name' => ['ar' => 'قابل لإعادة التعبئة', 'fr' => 'Rechargeable', 'en' => 'Refillable packaging'],
                'description' => [
                    'ar' => 'العبوة قابلة لإعادة التعبئة حسب تصريح البائع.',
                    'fr' => 'Le contenant est rechargeable, déclaré par le vendeur.',
                    'en' => 'The container is tagged refillable by the seller.',
                ],
                'icon' => '♻️', 'category' => 'packaging',
                'check_type' => 'green_attribute', 'check_value' => 'refillable',
                'points' => 7,
            ],
            [
                'code' => 'small_producer',
                'name' => ['ar' => 'منتج صغير', 'fr' => 'Petit producteur', 'en' => 'Small producer'],
                'description' => [
                    'ar' => 'البائع فرد أو جمعية أو مجموعة تنمية فلاحية صغيرة.',
                    'fr' => 'Le vendeur est un particulier, une association ou un petit collectif.',
                    'en' => 'The seller is an individual or a small cooperative/association, not a large company.',
                ],
                'icon' => '🤝', 'category' => 'producer',
                'check_type' => 'small_producer', 'check_value' => null,
                'points' => 10,
            ],
            [
                'code' => 'handmade',
                'name' => ['ar' => 'صناعة يدوية', 'fr' => 'Fait main', 'en' => 'Handmade'],
                'description' => [
                    'ar' => 'المنتج مصنوع يدويا حسب تصريح البائع.',
                    'fr' => 'Le produit est déclaré fait main par le vendeur.',
                    'en' => 'The listing is tagged handmade by the seller.',
                ],
                'icon' => '🧵', 'category' => 'craftsmanship',
                'check_type' => 'green_attribute', 'check_value' => 'handmade',
                'points' => 5,
            ],
            [
                'code' => 'women_led',
                'name' => ['ar' => 'بقيادة نساء', 'fr' => 'Dirigé par des femmes', 'en' => 'Women-led'],
                'description' => [
                    'ar' => 'المنتج من إنتاج مشروع بقيادة نساء حسب تصريح البائع.',
                    'fr' => 'Produit issu d\'une activité dirigée par des femmes, déclaré par le vendeur.',
                    'en' => 'The listing is tagged women-led by the seller.',
                ],
                'icon' => '👩‍🌾', 'category' => 'producer',
                'check_type' => 'green_attribute', 'check_value' => 'women_led',
                'points' => 5,
            ],
            [
                'code' => 'association_made',
                'name' => ['ar' => 'إنتاج جمعية', 'fr' => 'Produit par une association', 'en' => 'Association-made'],
                'description' => [
                    'ar' => 'المنتج من إنتاج جمعية أو مجموعة تنمية فلاحية.',
                    'fr' => 'Produit fabriqué par une association ou un GDA.',
                    'en' => 'The listing is tagged as made by an association or GDA.',
                ],
                'icon' => '🤝', 'category' => 'producer',
                'check_type' => 'green_attribute', 'check_value' => 'association_made',
                'points' => 5,
            ],
        ];

        foreach ($rules as $i => $rule) {
            GreenScoreRule::updateOrCreate(
                ['code' => $rule['code']],
                [...$rule, 'display_order' => $i],
            );
        }
    }
}
