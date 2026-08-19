<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategoryField;
use App\Models\CategoryRequirement;
use App\Models\CategoryRule;
use App\Models\PriceCap;
use Illuminate\Database\Seeder;

/**
 * The launch category tree.
 *
 * Note how little is special-cased. "Semences céréalières" needs a ministry
 * authorization and carries a state price ceiling; "Eau de rose" needs a
 * sanitary authorization and a lot number; "Sortie scolaire" needs insurance.
 * All three are rows here — the application code knows nothing about wheat,
 * rose water or schools.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // ---------------------------------------------------------- roots
        $intrants = $this->category('intrants', 'intrants', [
            'ar' => 'مستلزمات الفلاحة', 'fr' => 'Intrants agricoles', 'en' => 'Farm inputs',
        ], leaf: false, order: 1);

        $terroir = $this->category('terroir', 'terroir', [
            'ar' => 'منتجات محلية', 'fr' => 'Produits du terroir', 'en' => 'Local food',
        ], leaf: false, order: 2);

        $artisanat = $this->category('artisanat', 'artisanat', [
            'ar' => 'صناعات تقليدية', 'fr' => 'Artisanat & upcyclé', 'en' => 'Craft & upcycled',
        ], leaf: false, order: 3);

        $vegetal = $this->category('vegetal', 'vegetal', [
            'ar' => 'نباتات وزهور', 'fr' => 'Plantes & fleurs', 'en' => 'Plants & flowers',
        ], leaf: false, order: 4);

        $services = $this->category('services', 'services', [
            'ar' => 'خدمات', 'fr' => 'Services & ateliers', 'en' => 'Services & workshops',
        ], leaf: false, order: 5, type: 'service');

        // ------------------------------------------------- inputs branch
        // The whole branch requires a ministry authorization; children inherit.
        $this->requirement($intrants, 'moa_seed_authorization');

        $cereales = $this->category('semences-cereales', 'intrants/semences/cereales', [
            'ar' => 'بذور الحبوب', 'fr' => 'Semences céréalières', 'en' => 'Cereal seed',
        ], parent: $intrants);

        $this->fields($cereales, [
            ['variety', ['fr' => 'Variété', 'ar' => 'الصنف', 'en' => 'Variety'], 'string', true, true],
            ['certification_class', ['fr' => 'Classe', 'ar' => 'الفئة', 'en' => 'Class'], 'select', true, true,
                ['options' => ['prebase' => 'Pré-base', 'base' => 'Base', 'r1' => 'R1', 'r2' => 'R2']]],
            ['germination_rate', ['fr' => 'Taux de germination', 'ar' => 'نسبة الإنبات', 'en' => 'Germination rate'], 'number', true, false, ['unit' => '%', 'min' => 0, 'max' => 100]],
            ['purity_rate', ['fr' => 'Pureté', 'ar' => 'النقاوة', 'en' => 'Purity'], 'number', true, false, ['unit' => '%', 'min' => 0, 'max' => 100]],
            ['production_year', ['fr' => 'Année de production', 'ar' => 'سنة الإنتاج', 'en' => 'Production year'], 'integer', true, false],
            ['under_exploitation_contract', ['fr' => 'Variété sous contrat', 'ar' => 'صنف تحت عقد', 'en' => 'Under contract'], 'boolean', false, false],
        ]);

        $this->rule($cereales, ['is_heavy' => true, 'requires_lot_number' => true]);

        // LC-02 — the 2026/27 ceilings published by the Ministry of Agriculture.
        // Millimes: 170 TND/quintal = 170000.
        $caps = [
            ['ble-dur', 170_000, ['fr' => 'Blé dur', 'ar' => 'قمح صلب', 'en' => 'Durum wheat']],
            ['ble-tendre', 140_000, ['fr' => 'Blé tendre', 'ar' => 'قمح لين', 'en' => 'Soft wheat']],
            ['orge-triticale', 130_000, ['fr' => 'Orge & triticale', 'ar' => 'شعير وتريتيكال', 'en' => 'Barley & triticale']],
            ['orge-commerciale', 115_000, ['fr' => 'Orge commerciale ordinaire', 'ar' => 'شعير تجاري عادي', 'en' => 'Ordinary commercial barley']],
        ];

        foreach ($caps as $i => [$slug, $price, $name]) {
            $sub = $this->category('semences-'.$slug, 'intrants/semences/cereales/'.$slug, $name, parent: $cereales, order: $i);

            PriceCap::updateOrCreate(
                ['category_id' => $sub->id, 'season_label' => '2026/2027'],
                [
                    'unit' => 'quintal',
                    'max_price' => $price,
                    'contract_surcharge_pct' => 3.00,
                    'effective_from' => '2026-08-18',
                    'source_ref' => 'https://www.tunisie-tribune.com/2026/08/18/le-ministere-de-lagriculture-fixe-les-prix-des-semences/',
                ],
            );
        }

        $bioIntrants = $this->category('intrants-bio', 'intrants/bio', [
            'ar' => 'مدخلات بيولوجية', 'fr' => 'Fertilisants & intrants bio', 'en' => 'Organic inputs',
        ], parent: $intrants, order: 2);

        $this->fields($bioIntrants, [
            ['npk', ['fr' => 'NPK', 'ar' => 'NPK', 'en' => 'NPK'], 'string', false, true],
            ['organic_matter', ['fr' => 'Matière organique', 'ar' => 'المادة العضوية', 'en' => 'Organic matter'], 'number', false, false, ['unit' => '%']],
            ['homologation_number', ['fr' => "N° d'homologation", 'ar' => 'رقم المصادقة', 'en' => 'Homologation no.'], 'string', true, false],
            ['dose_per_ha', ['fr' => 'Dose par hectare', 'ar' => 'الجرعة للهكتار', 'en' => 'Dose per hectare'], 'number', true, false, ['unit' => 'kg/ha']],
            ['approved_for_organic', ['fr' => 'Utilisable en bio', 'ar' => 'صالح للفلاحة البيولوجية', 'en' => 'Approved for organic'], 'boolean', false, true],
        ]);

        $this->rule($bioIntrants, ['is_heavy' => true, 'requires_lot_number' => true]);

        // ------------------------------------------------- food branch
        // Anything edible needs a sanitary authorization and a lot number.
        // Both are set once, on the branch, and every child inherits them.
        $this->requirement($terroir, 'sanitary_authorization');
        $this->rule($terroir, ['is_perishable' => true, 'requires_lot_number' => true, 'max_delivery_days' => 3]);

        $this->fields($terroir, [
            ['ingredients', ['fr' => 'Ingrédients', 'ar' => 'المكونات', 'en' => 'Ingredients'], 'text', true, false],
            ['allergens', ['fr' => 'Allergènes', 'ar' => 'مسببات الحساسية', 'en' => 'Allergens'], 'string', false, false],
            ['net_weight', ['fr' => 'Poids net', 'ar' => 'الوزن الصافي', 'en' => 'Net weight'], 'number', true, false, ['unit' => 'g']],
            ['production_date', ['fr' => 'Date de production', 'ar' => 'تاريخ الإنتاج', 'en' => 'Production date'], 'date', true, false],
            ['shelf_life_days', ['fr' => 'DLC (jours)', 'ar' => 'مدة الصلاحية', 'en' => 'Shelf life (days)'], 'integer', true, false],
            ['storage', ['fr' => 'Conservation', 'ar' => 'التخزين', 'en' => 'Storage'], 'string', false, false],
        ]);

        $this->category('huile-olive', 'terroir/huile-olive', [
            'ar' => 'زيت الزيتون', 'fr' => "Huile d'olive", 'en' => 'Olive oil',
        ], parent: $terroir, order: 1);

        $eauDeRose = $this->category('eau-de-rose', 'terroir/distillats', [
            'ar' => 'ماء الورد والقطران', 'fr' => 'Eaux florales & distillats', 'en' => 'Floral waters',
        ], parent: $terroir, order: 2);

        // Rose water is an April–May product. Seasonality is a field, not a
        // sentence in the description.
        $this->fields($eauDeRose, [
            ['distillation_method', ['fr' => 'Méthode de distillation', 'ar' => 'طريقة التقطير', 'en' => 'Distillation method'], 'string', false, false],
        ]);

        $this->category('harissa-conserves', 'terroir/conserves', [
            'ar' => 'هريسة ومصبرات', 'fr' => 'Harissa & conserves', 'en' => 'Harissa & preserves',
        ], parent: $terroir, order: 3);

        // ---------------------------------------------- artisanat branch
        $this->requirement($artisanat, 'cin_identity');

        $this->fields($artisanat, [
            ['materials', ['fr' => 'Matériaux', 'ar' => 'المواد', 'en' => 'Materials'], 'string', true, true],
            ['recycled_content', ['fr' => 'Contenu recyclé', 'ar' => 'نسبة إعادة التدوير', 'en' => 'Recycled content'], 'number', false, true, ['unit' => '%']],
            ['technique', ['fr' => 'Technique', 'ar' => 'التقنية', 'en' => 'Technique'], 'string', false, true],
            ['dimensions', ['fr' => 'Dimensions', 'ar' => 'الأبعاد', 'en' => 'Dimensions'], 'string', false, false],
        ]);

        $this->rule($artisanat, ['is_fragile' => true]);

        $this->category('poterie', 'artisanat/poterie', ['ar' => 'فخار', 'fr' => 'Poterie', 'en' => 'Pottery'], parent: $artisanat, order: 1);
        $this->category('textile', 'artisanat/textile', ['ar' => 'نسيج', 'fr' => 'Textile & tissage', 'en' => 'Textile'], parent: $artisanat, order: 2);
        $this->category('upcycle', 'artisanat/upcycle', ['ar' => 'إعادة تدوير', 'fr' => 'Objets upcyclés', 'en' => 'Upcycled goods'], parent: $artisanat, order: 3);

        $cosmetiques = $this->category('cosmetiques', 'artisanat/cosmetiques', [
            'ar' => 'مستحضرات طبيعية', 'fr' => 'Cosmétiques naturels', 'en' => 'Natural cosmetics',
        ], parent: $artisanat, order: 4);

        $this->fields($cosmetiques, [
            ['ingredient_list', ['fr' => 'Liste INCI', 'ar' => 'قائمة المكونات', 'en' => 'Ingredient list'], 'text', true, false],
            ['skin_type', ['fr' => 'Type de peau', 'ar' => 'نوع البشرة', 'en' => 'Skin type'], 'select', false, true,
                ['options' => ['all' => 'Tous', 'dry' => 'Sèche', 'oily' => 'Grasse', 'sensitive' => 'Sensible']]],
            ['pao_months', ['fr' => 'PAO (mois après ouverture)', 'ar' => 'مدة الاستعمال بعد الفتح', 'en' => 'Period after opening'], 'integer', false, false],
        ]);

        $this->rule($cosmetiques, ['requires_lot_number' => true]);

        // ------------------------------------------------ plants branch
        $this->requirement($vegetal, 'nursery_agrement');

        $pepiniere = $this->category('pepiniere', 'vegetal/pepiniere', [
            'ar' => 'مشتل', 'fr' => 'Pépinière', 'en' => 'Nursery',
        ], parent: $vegetal, order: 1);

        $this->fields($pepiniere, [
            ['botanical_name', ['fr' => 'Nom botanique', 'ar' => 'الاسم العلمي', 'en' => 'Botanical name'], 'string', true, true],
            ['container_size', ['fr' => 'Taille du conteneur', 'ar' => 'حجم الوعاء', 'en' => 'Container size'], 'string', true, false],
            ['plant_age_months', ['fr' => 'Âge du plant', 'ar' => 'عمر الشتلة', 'en' => 'Plant age'], 'integer', false, false, ['unit' => 'mois']],
            ['rootstock', ['fr' => 'Porte-greffe', 'ar' => 'الأصل المطعم', 'en' => 'Rootstock'], 'string', false, false],
            ['water_need', ['fr' => 'Besoin en eau', 'ar' => 'الحاجة للماء', 'en' => 'Water need'], 'select', true, true,
                ['options' => ['low' => 'Faible', 'medium' => 'Moyen', 'high' => 'Élevé']]],
            ['sun_exposure', ['fr' => 'Exposition', 'ar' => 'التعرض للشمس', 'en' => 'Sun exposure'], 'select', false, true,
                ['options' => ['full_sun' => 'Plein soleil', 'partial' => 'Mi-ombre', 'shade' => 'Ombre']]],
            ['toxicity', ['fr' => 'Toxicité (enfants / animaux)', 'ar' => 'السمية', 'en' => 'Toxicity'], 'select', true, true,
                ['options' => ['none' => 'Aucune', 'mild' => 'Légère', 'toxic' => 'Toxique']]],
            ['peat_free', ['fr' => 'Substrat sans tourbe', 'ar' => 'ركيزة بدون خث', 'en' => 'Peat-free substrate'], 'boolean', false, true],
            ['pollination_partner', ['fr' => 'Pollinisateur requis', 'ar' => 'ملقح مطلوب', 'en' => 'Pollination partner'], 'string', false, false],
        ]);

        $this->rule($pepiniere, ['is_live' => true, 'is_fragile' => true, 'max_delivery_days' => 2]);

        $fleuriste = $this->category('fleuriste', 'vegetal/fleuriste', [
            'ar' => 'بائع الزهور', 'fr' => 'Fleuriste', 'en' => 'Florist',
        ], parent: $vegetal, order: 2);

        $this->fields($fleuriste, [
            ['stem_count', ['fr' => 'Nombre de tiges', 'ar' => 'عدد السيقان', 'en' => 'Stem count'], 'integer', true, false],
            ['vase_life_days', ['fr' => 'Tenue en vase', 'ar' => 'مدة البقاء', 'en' => 'Vase life'], 'integer', false, false, ['unit' => 'jours']],
            ['origin', ['fr' => 'Origine', 'ar' => 'المنشأ', 'en' => 'Origin'], 'select', true, true,
                ['options' => ['local' => 'Cultivé localement', 'imported' => 'Importé']]],
            ['foam_free', ['fr' => 'Sans mousse florale', 'ar' => 'بدون إسفنج', 'en' => 'Foam-free'], 'boolean', false, true],
        ]);

        $this->rule($fleuriste, ['is_perishable' => true, 'is_fragile' => true, 'max_delivery_days' => 1]);

        // ---------------------------------------------- services branch
        $conseil = $this->category('conseil-agronomique', 'services/conseil', [
            'ar' => 'استشارة فلاحية', 'fr' => 'Conseil agronomique', 'en' => 'Agronomy consulting',
        ], parent: $services, order: 1, type: 'service');

        $this->fields($conseil, [
            ['specialty', ['fr' => 'Spécialité', 'ar' => 'الاختصاص', 'en' => 'Specialty'], 'string', true, true],
            ['mode', ['fr' => 'Mode', 'ar' => 'النمط', 'en' => 'Mode'], 'select', true, true,
                ['options' => ['remote' => 'À distance', 'onsite' => 'Sur site']]],
            ['duration_min', ['fr' => 'Durée', 'ar' => 'المدة', 'en' => 'Duration'], 'integer', true, false, ['unit' => 'min']],
        ]);

        // Hosting groups — especially children — requires verified insurance.
        $ateliers = $this->category('ateliers-sorties', 'services/ateliers', [
            'ar' => 'ورشات وخرجات مدرسية', 'fr' => 'Ateliers & sorties scolaires', 'en' => 'Workshops & school outings',
        ], parent: $services, order: 2, type: 'experience');

        $this->requirement($ateliers, 'liability_insurance');

        $this->fields($ateliers, [
            ['capacity_min', ['fr' => 'Groupe minimum', 'ar' => 'أدنى عدد', 'en' => 'Min group'], 'integer', true, false],
            ['capacity_max', ['fr' => 'Groupe maximum', 'ar' => 'أقصى عدد', 'en' => 'Max group'], 'integer', true, false],
            ['age_min', ['fr' => 'Âge minimum', 'ar' => 'السن الأدنى', 'en' => 'Min age'], 'integer', true, false],
            ['age_max', ['fr' => 'Âge maximum', 'ar' => 'السن الأقصى', 'en' => 'Max age'], 'integer', false, false],
            ['supervision_ratio', ['fr' => 'Taux d\'encadrement', 'ar' => 'نسبة التأطير', 'en' => 'Supervision ratio'], 'string', true, false],
            ['animation_language', ['fr' => "Langue d'animation", 'ar' => 'لغة التنشيط', 'en' => 'Animation language'], 'select', true, true,
                ['options' => ['ar' => 'العربية', 'fr' => 'Français', 'en' => 'English']]],
            ['learning_objectives', ['fr' => 'Objectifs pédagogiques', 'ar' => 'الأهداف التربوية', 'en' => 'Learning objectives'], 'text', false, false],
            ['accessibility', ['fr' => 'Accessibilité', 'ar' => 'إمكانية الوصول', 'en' => 'Accessibility'], 'string', false, false],
            ['weather_policy', ['fr' => 'Politique météo', 'ar' => 'سياسة الطقس', 'en' => 'Weather policy'], 'text', true, false],
        ]);

        $this->rule($ateliers, ['allows_group_booking' => true]);
    }

    // ------------------------------------------------------------ helpers

    private function category(
        string $slug,
        string $path,
        array $name,
        ?Category $parent = null,
        bool $leaf = true,
        int $order = 0,
        string $type = 'product',
    ): Category {
        return Category::updateOrCreate(
            ['slug' => $slug],
            [
                'parent_id' => $parent?->id,
                'name' => $name,
                'path' => $path,
                'is_leaf' => $leaf,
                'listing_type' => $type,
                'display_order' => $order,
                'is_active' => true,
            ],
        );
    }

    private function requirement(Category $category, string $credentialTypeCode): void
    {
        CategoryRequirement::updateOrCreate(
            ['category_id' => $category->id, 'credential_type_code' => $credentialTypeCode],
            ['is_mandatory' => true],
        );
    }

    private function rule(Category $category, array $flags): void
    {
        CategoryRule::updateOrCreate(['category_id' => $category->id], $flags);
    }

    private function fields(Category $category, array $definitions): void
    {
        foreach ($definitions as $i => $definition) {
            [$key, $label, $type, $required, $filterable] = $definition;
            $extra = $definition[5] ?? [];

            CategoryField::updateOrCreate(
                ['category_id' => $category->id, 'key' => $key],
                [
                    'label' => $label,
                    'data_type' => $type,
                    'required' => $required,
                    'filterable' => $filterable,
                    'unit' => $extra['unit'] ?? null,
                    'options' => $extra['options'] ?? null,
                    'min_value' => $extra['min'] ?? null,
                    'max_value' => $extra['max'] ?? null,
                    'display_order' => $i,
                ],
            );
        }
    }
}
