<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Credential;
use App\Models\CredentialType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * FR-020 — the seller picks the categories they intend to sell in, and the
 * system computes exactly which credentials they must supply. Nothing more.
 *
 * This ordering matters. Asking every seller for every document is how you
 * lose the home producer who only wanted to sell soap.
 */
class SellerOnboardingController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        return view('seller.onboarding', [
            'categories' => Category::active()->where('is_leaf', true)->orderBy('path')->get(),
            'credentials' => $user->credentials()->with('credentialType')->get(),
            'held' => $user->approvedCredentialCodes(),
            'badges' => $user->activeBadges(),
        ]);
    }

    /** Which credential types are needed for a chosen set of categories. */
    public function requirements(Request $request): View
    {
        $ids = array_filter((array) $request->input('categories', []));

        $needed = Category::whereIn('id', $ids)
            ->get()
            ->flatMap(fn (Category $c) => $c->mandatoryCredentialCodes())
            ->unique()
            ->values();

        $held = $request->user()->approvedCredentialCodes();

        return view('seller.requirements', [
            'types' => CredentialType::whereIn('code', $needed)->get(),
            'missing' => $needed->reject(fn ($c) => $held->contains($c))->values(),
            'selected' => $ids,
        ]);
    }

    public function storeCredential(Request $request): RedirectResponse
    {
        $type = CredentialType::findOrFail($request->input('credential_type_code'));

        $validated = $request->validate([
            'credential_type_code' => ['required', 'string'],
            'number' => [$type->requires_number ? 'required' : 'nullable', 'string', 'max:128'],
            'issuer' => ['nullable', 'string', 'max:190'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => [$type->requires_expiry ? 'required' : 'nullable', 'date', 'after:today'],
            'document' => [$type->requires_document ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
        ]);

        // NFR-08 — private disk. Never the public one, never a guessable path.
        $path = $request->hasFile('document')
            ? $request->file('document')->store('', 'credentials')
            : null;

        Credential::create([
            'user_id' => $request->user()->id,
            'credential_type_code' => $validated['credential_type_code'],
            'number' => $validated['number'] ?? null,
            'issuer' => $validated['issuer'] ?? null,
            'issued_at' => $validated['issued_at'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'document_path' => $path,
            'status' => 'pending',
        ]);

        return back()->with('status', __('seller.credential_submitted'));
    }
}
