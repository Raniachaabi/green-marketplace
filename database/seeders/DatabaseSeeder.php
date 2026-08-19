<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Order matters: credential types before categories that require
        // them, categories before listings that sit in them.
        $this->call([
            CredentialTypeSeeder::class,
            GreenAttributeSeeder::class,
            CategorySeeder::class,
            ReferenceDataSeeder::class,
            DemoSeeder::class,
        ]);
    }
}
