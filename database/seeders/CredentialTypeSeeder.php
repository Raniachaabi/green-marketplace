<?php

namespace Database\Seeders;

use App\Models\Badge;
use App\Models\CredentialType;
use Illuminate\Database\Seeder;

/**
 * The verification vocabulary, grounded in the actual Tunisian regime.
 *
 * Each type names its legal reference so the admin reviewing a document knows
 * what they are looking at, and so the public badge disclosure page can cite
 * it. Vagueness here is what turns a badge into a liability.
 */
class CredentialTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'code' => 'cin_identity',
                'label' => ['ar' => 'هوية موثقة', 'fr' => 'Identité vérifiée', 'en' => 'Verified identity'],
                'issuing_body' => null,
                'legal_reference' => null,
                'requires_expiry' => false,
                'requires_number' => true,
                'requires_document' => true,
            ],
            [
                'code' => 'patente',
                'label' => ['ar' => 'بطاقة تعريف جبائية', 'fr' => 'Patente / matricule fiscal', 'en' => 'Business registration'],
                'issuing_body' => 'Ministère des Finances',
                'legal_reference' => null,
                'requires_expiry' => false,
            ],
            [
                'code' => 'association_statutes',
                'label' => ['ar' => 'قانون أساسي لجمعية', 'fr' => "Statuts d'association / GDA / SMSA", 'en' => 'Association statutes'],
                'issuing_body' => 'JORT / Ministère de l\'Intérieur',
                'requires_expiry' => false,
            ],
            [
                'code' => 'onat_artisan',
                'label' => ['ar' => 'بطاقة حرفي', 'fr' => "Carte d'artisan ONAT", 'en' => 'ONAT artisan card'],
                'issuing_body' => 'Office National de l\'Artisanat Tunisien',
                'requires_expiry' => true,
            ],
            [
                'code' => 'sanitary_authorization',
                'label' => ['ar' => 'ترخيص صحي', 'fr' => 'Autorisation sanitaire', 'en' => 'Sanitary authorization'],
                'description' => [
                    'fr' => "Obligatoire pour tout produit comestible. Aucun produit alimentaire n'est publié sans elle.",
                    'en' => 'Mandatory for any edible product. No food listing goes live without it.',
                ],
                'issuing_body' => 'Ministère de la Santé / ANCSEP',
                'legal_reference' => 'Loi n° 2019-25 (sécurité sanitaire des aliments)',
                'requires_expiry' => true,
            ],
            [
                'code' => 'moa_seed_authorization',
                'label' => ['ar' => 'ترخيص بيع البذور والشتلات', 'fr' => 'Autorisation semences et plants', 'en' => 'Seed & plant commerce authorization'],
                'issuing_body' => 'Ministère de l\'Agriculture',
                'legal_reference' => 'Loi n° 99-42 (semences, plants et obtentions végétales)',
                'requires_expiry' => true,
            ],
            [
                'code' => 'nursery_agrement',
                'label' => ['ar' => 'ترخيص مشتل', 'fr' => 'Agrément pépiniériste', 'en' => 'Nursery licence'],
                'issuing_body' => 'Ministère de l\'Agriculture',
                'legal_reference' => 'Loi n° 99-42',
                'requires_expiry' => true,
            ],
            [
                'code' => 'phytosanitary_certificate',
                'label' => ['ar' => 'شهادة صحة نباتية', 'fr' => 'Certificat phytosanitaire', 'en' => 'Phytosanitary certificate'],
                'issuing_body' => 'Ministère de l\'Agriculture',
                'legal_reference' => 'Arrêté du 19 février 2016',
                'requires_expiry' => true,
            ],
            [
                'code' => 'organic_certificate',
                'label' => ['ar' => 'شهادة فلاحة بيولوجية', 'fr' => 'Certificat agriculture biologique', 'en' => 'Organic certificate'],
                'description' => [
                    'fr' => 'Délivré par un organisme accrédité (Ecocert, CCPB TN-BIO-008, …) selon la liste CTAB.',
                    'en' => 'Issued by an accredited body (Ecocert, CCPB TN-BIO-008, …) per the CTAB list.',
                ],
                'issuing_body' => 'Organisme accrédité (liste CTAB)',
                'legal_reference' => 'Loi n° 99-30 (agriculture biologique)',
                'requires_expiry' => true,
            ],
            [
                'code' => 'liability_insurance',
                'label' => ['ar' => 'تأمين المسؤولية المدنية', 'fr' => 'Assurance responsabilité civile', 'en' => 'Public liability insurance'],
                'description' => [
                    'fr' => "Obligatoire pour accueillir des groupes (ateliers, sorties scolaires, visites à la ferme).",
                    'en' => 'Mandatory to host groups (workshops, school outings, farm visits).',
                ],
                'requires_expiry' => true,
            ],
        ];

        foreach ($types as $type) {
            CredentialType::updateOrCreate(['code' => $type['code']], $type);
        }

        // FR-023 / FR-024 — badges, each tied to the credential that earns it.
        $badges = [
            ['code' => 'verified_seller', 'label' => ['ar' => 'بائع موثق', 'fr' => 'Vendeur vérifié', 'en' => 'Verified seller'], 'types' => ['cin_identity']],
            ['code' => 'registered_business', 'label' => ['ar' => 'مؤسسة مسجلة', 'fr' => 'Entreprise enregistrée', 'en' => 'Registered business'], 'types' => ['patente']],
            ['code' => 'registered_association', 'label' => ['ar' => 'جمعية مسجلة', 'fr' => 'Association enregistrée', 'en' => 'Registered association'], 'types' => ['association_statutes']],
            ['code' => 'onat_artisan', 'label' => ['ar' => 'حرفي معترف به', 'fr' => 'Artisan reconnu ONAT', 'en' => 'ONAT artisan'], 'types' => ['onat_artisan']],
            ['code' => 'sanitary_ok', 'label' => ['ar' => 'ترخيص صحي', 'fr' => 'Autorisation sanitaire', 'en' => 'Sanitary authorization'], 'types' => ['sanitary_authorization']],
            ['code' => 'licensed_dealer', 'label' => ['ar' => 'موزع معتمد', 'fr' => 'Revendeur agréé', 'en' => 'Licensed dealer'], 'types' => ['moa_seed_authorization']],
            ['code' => 'licensed_nursery', 'label' => ['ar' => 'مشتل معتمد', 'fr' => 'Pépiniériste agréé', 'en' => 'Licensed nursery'], 'types' => ['nursery_agrement']],
            ['code' => 'organic_certified', 'label' => ['ar' => 'بيولوجي معتمد', 'fr' => 'Bio certifié', 'en' => 'Organic certified'], 'types' => ['organic_certificate']],
            ['code' => 'group_insured', 'label' => ['ar' => 'استقبال مجموعات مؤمّن', 'fr' => 'Accueil de groupes assuré', 'en' => 'Insured for groups'], 'types' => ['liability_insurance']],
        ];

        foreach ($badges as $badge) {
            $model = Badge::updateOrCreate(
                ['code' => $badge['code']],
                ['label' => $badge['label']],
            );

            $model->credentialTypes()->sync($badge['types']);
        }
    }
}
