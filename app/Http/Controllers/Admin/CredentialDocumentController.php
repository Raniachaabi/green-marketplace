<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Credential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The one place a credential document can actually be opened.
 *
 * The verification queue (CredentialResource) approves or rejects a
 * document without ever letting the admin see it otherwise — this closes
 * that gap. The file lives on a private disk (never `public`) and is
 * streamed only to an authenticated admin, never linked or cached
 * anywhere a non-admin request could reach it.
 */
class CredentialDocumentController extends Controller
{
    public function show(Request $request, Credential $credential): StreamedResponse
    {
        abort_unless($request->user()?->is_admin, 403);
        abort_unless($credential->document_path, 404);
        abort_unless(Storage::disk('credentials')->exists($credential->document_path), 404);

        return Storage::disk('credentials')->response($credential->document_path);
    }
}
