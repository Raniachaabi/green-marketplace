<?php

namespace Database\Seeders;

use App\Models\GreenAttribute;
use Illuminate\Database\Seeder;

class GreenAttributeSeeder extends Seeder
{
    public function run(): void
    {
        $attributes = [
            ['handmade', ['ar' => 'صناعة يدوية', 'fr' => 'Fait main', 'en' => 'Handmade']],
            ['upcycled', ['ar' => 'معاد تدويره', 'fr' => 'Upcyclé', 'en' => 'Upcycled']],
            ['plastic_free', ['ar' => 'بدون بلاستيك', 'fr' => 'Sans plastique', 'en' => 'Plastic-free']],
            ['refillable', ['ar' => 'قابل لإعادة التعبئة', 'fr' => 'Rechargeable', 'en' => 'Refillable']],
            ['locally_sourced', ['ar' => 'منتج محلي', 'fr' => 'Sourcé localement', 'en' => 'Locally sourced']],
            ['organic_certified', ['ar' => 'بيولوجي معتمد', 'fr' => 'Bio certifié', 'en' => 'Organic certified']],
            ['women_led', ['ar' => 'بقيادة نساء', 'fr' => 'Dirigé par des femmes', 'en' => 'Women-led']],
            ['association_made', ['ar' => 'إنتاج جمعية', 'fr' => 'Produit par une association', 'en' => 'Association-made']],
            // Plant and flower specifics — the ones that separate a real green
            // florist from a green-painted one.
            ['locally_grown', ['ar' => 'مزروع محليا', 'fr' => 'Cultivé localement', 'en' => 'Locally grown']],
            ['native_species', ['ar' => 'نوع محلي', 'fr' => 'Espèce indigène', 'en' => 'Native species']],
            ['drought_tolerant', ['ar' => 'مقاوم للجفاف', 'fr' => 'Résistant à la sécheresse', 'en' => 'Drought-tolerant']],
            ['peat_free', ['ar' => 'بدون خث', 'fr' => 'Sans tourbe', 'en' => 'Peat-free']],
            ['foam_free', ['ar' => 'بدون إسفنج زهري', 'fr' => 'Sans mousse florale', 'en' => 'Foam-free']],
            ['pollinator_friendly', ['ar' => 'صديق للملقحات', 'fr' => 'Favorable aux pollinisateurs', 'en' => 'Pollinator-friendly']],
        ];

        foreach ($attributes as $i => [$code, $label]) {
            GreenAttribute::updateOrCreate(
                ['code' => $code],
                ['label' => $label, 'display_order' => $i],
            );
        }
    }
}
