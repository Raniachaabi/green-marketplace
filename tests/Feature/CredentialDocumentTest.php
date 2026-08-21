<?php

namespace Tests\Feature;

use App\Models\Credential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\CreatesMarketplace;
use Tests\TestCase;

class CredentialDocumentTest extends TestCase
{
    use CreatesMarketplace, RefreshDatabase;

    public function test_an_admin_can_open_the_uploaded_document(): void
    {
        Storage::fake('credentials');

        $admin = User::create(['phone' => '+21670000000', 'full_name' => 'Admin', 'slug' => 'admin']);
        $admin->forceFill(['is_admin' => true])->save();
        $seller = $this->seller();
        $this->credentialType('cin_identity');

        $path = UploadedFile::fake()->create('id-card.pdf', 100)->store('', 'credentials');
        $credential = Credential::create([
            'user_id' => $seller->id,
            'credential_type_code' => 'cin_identity',
            'document_path' => $path,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.credentials.document', $credential))
            ->assertOk();
    }

    public function test_a_non_admin_cannot_open_the_document(): void
    {
        Storage::fake('credentials');

        $seller = $this->seller();
        $this->credentialType('cin_identity');

        $path = UploadedFile::fake()->create('id-card.pdf', 100)->store('', 'credentials');
        $credential = Credential::create([
            'user_id' => $seller->id,
            'credential_type_code' => 'cin_identity',
            'document_path' => $path,
            'status' => 'pending',
        ]);

        $this->actingAs($seller)
            ->get(route('admin.credentials.document', $credential))
            ->assertForbidden();
    }
}
