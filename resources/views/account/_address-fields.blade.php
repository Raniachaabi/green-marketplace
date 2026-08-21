{{-- Shared by the add form and each address's inline edit form. $address is null when adding. --}}
<div class="grid gap-3 sm:grid-cols-2">
    <label class="text-sm sm:col-span-2">
        <span class="text-stone-500 dark:text-muted-foreground">{{ __('account.label') }}</span>
        <input name="label" value="{{ old('label', $address->label ?? '') }}" placeholder="{{ __('account.label_placeholder') }}"
               class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
    </label>

    <label class="text-sm">
        <span class="text-stone-500 dark:text-muted-foreground">{{ __('account.contact_name') }} *</span>
        <input name="contact_name" required value="{{ old('contact_name', $address->contact_name ?? '') }}"
               class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
    </label>

    <label class="text-sm">
        <span class="text-stone-500 dark:text-muted-foreground">{{ __('account.contact_phone') }} *</span>
        <input name="contact_phone" required value="{{ old('contact_phone', $address->contact_phone ?? '') }}"
               class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
    </label>

    <label class="text-sm">
        <span class="text-stone-500 dark:text-muted-foreground">{{ __('account.governorate') }} *</span>
        <select name="governorate" required
                class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
            <option value="">—</option>
            @foreach(config('marketplace.governorates') as $governorate)
                <option value="{{ $governorate }}" @selected(old('governorate', $address->governorate ?? '') === $governorate)>
                    {{ __('governorate.'.$governorate) }}
                </option>
            @endforeach
        </select>
    </label>

    <label class="text-sm">
        <span class="text-stone-500 dark:text-muted-foreground">{{ __('account.delegation') }}</span>
        <input name="delegation" value="{{ old('delegation', $address->delegation ?? '') }}"
               class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
    </label>

    <label class="text-sm">
        <span class="text-stone-500 dark:text-muted-foreground">{{ __('account.locality') }}</span>
        <input name="locality" value="{{ old('locality', $address->locality ?? '') }}"
               class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
    </label>

    <label class="text-sm">
        <span class="text-stone-500 dark:text-muted-foreground">{{ __('account.street') }}</span>
        <input name="street" value="{{ old('street', $address->street ?? '') }}"
               class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
    </label>

    <label class="text-sm">
        <span class="text-stone-500 dark:text-muted-foreground">{{ __('account.postal_code') }}</span>
        <input name="postal_code" value="{{ old('postal_code', $address->postal_code ?? '') }}"
               class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
    </label>

    <label class="flex items-center gap-2 text-sm sm:col-span-2">
        <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $address->is_default ?? false))
               class="rounded border-stone-300 text-primary focus:ring-primary dark:border-input dark:bg-muted">
        {{ __('account.default_address') }}
    </label>
</div>
