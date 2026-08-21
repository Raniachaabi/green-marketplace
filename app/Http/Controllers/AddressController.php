<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Buyer address book.
 *
 * Checkout reads straight from here (Address::where('user_id', ...)) — this
 * is the only place a buyer can ever put a delivery address on file. Without
 * it, checkout is a dead end for anyone who has never bought before.
 */
class AddressController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.addresses', [
            'addresses' => $request->user()->addresses()->orderByDesc('is_default')->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $address = $request->user()->addresses()->create($data);

        if ($data['is_default'] || $request->user()->addresses()->count() === 1) {
            $this->makeDefault($request, $address);
        }

        return back()->with('status', __('account.address_saved'));
    }

    public function update(Request $request, Address $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 403);

        $data = $this->validated($request);
        $address->update($data);

        if ($data['is_default']) {
            $this->makeDefault($request, $address);
        }

        return back()->with('status', __('account.address_saved'));
    }

    public function destroy(Request $request, Address $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 403);

        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $next = $request->user()->addresses()->first();
            $next?->update(['is_default' => true]);
        }

        return back()->with('status', __('account.address_deleted'));
    }

    public function setDefault(Request $request, Address $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 403);

        $this->makeDefault($request, $address);

        return back()->with('status', __('account.address_default_set'));
    }

    private function makeDefault(Request $request, Address $address): void
    {
        $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        $address->update(['is_default' => true]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:64'],
            'contact_name' => ['required', 'string', 'max:190'],
            'contact_phone' => ['required', 'string', 'max:32'],
            'governorate' => ['required', 'string', 'in:'.implode(',', config('marketplace.governorates'))],
            'delegation' => ['nullable', 'string', 'max:64'],
            'locality' => ['nullable', 'string', 'max:190'],
            'street' => ['nullable', 'string', 'max:190'],
            'postal_code' => ['nullable', 'string', 'max:8'],
        ]);

        $data['is_default'] = $request->boolean('is_default');

        return $data;
    }
}
