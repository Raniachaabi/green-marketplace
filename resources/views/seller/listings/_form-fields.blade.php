{{--
    Shared between create.blade.php and edit.blade.php. Both provide
    $category, $fields, $rule, $priceCap, $greenAttributes, $selectedGreen
    and $listing (null on create) in scope before including this.
--}}

<section class="rounded-2xl border border-stone-200 bg-white p-5 shadow-card dark:border-border dark:bg-card">
    <h2 class="mb-4 flex items-center gap-2 font-semibold text-leaf-900 dark:text-foreground">
        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-gradient-card-tint text-xs font-bold text-leaf-700 dark:text-primary">1</span>
        {{ __('seller.section_details') }}
    </h2>

    <div class="grid gap-4 sm:grid-cols-2">
        @foreach(config('marketplace.locales') as $code => $meta)
            <label class="block text-sm">
                <span class="text-stone-500 dark:text-muted-foreground">{{ __('seller.title') }} ({{ $meta['name'] }})</span>
                <input name="title[{{ $code }}]" dir="{{ $meta['dir'] }}"
                       value="{{ old('title.'.$code, data_get($listing?->title, $code)) }}"
                       class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
            </label>
        @endforeach
    </div>

    <label class="mt-4 block text-sm">
        <span class="text-stone-500 dark:text-muted-foreground">{{ __('seller.description') }}</span>
        <textarea name="description[{{ app()->getLocale() }}]" rows="4"
                  class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring"
                  >{{ old('description.'.app()->getLocale(), data_get($listing?->description, app()->getLocale())) }}</textarea>
    </label>
</section>

<section class="rounded-2xl border border-stone-200 bg-white p-5 shadow-card dark:border-border dark:bg-card"
         x-data="{
             availability: '{{ old('availability_model', $listing->availability_model->value ?? 'in_stock') }}',
             tracksStock() { return this.availability === 'in_stock' || this.availability === 'limited_batch' },
         }">
    <h2 class="mb-4 flex items-center gap-2 font-semibold text-leaf-900 dark:text-foreground">
        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-gradient-card-tint text-xs font-bold text-leaf-700 dark:text-primary">2</span>
        {{ __('seller.section_pricing') }}
    </h2>

    <div class="grid gap-4 sm:grid-cols-2">
        <label class="text-sm">
            <span class="text-stone-500 dark:text-muted-foreground">{{ __('seller.price') }} (TND)</span>
            <input name="price" type="number" step="0.001" min="0" required
                   value="{{ old('price', $listing ? \App\Support\Money::toDinars($listing->price) : '') }}"
                   class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
            @if($priceCap)
                <span class="mt-1 block text-xs font-medium text-amber-700 dark:text-amber-400">
                    {{ __('seller.max_price', ['ceiling' => $priceCap->formattedCeiling()]) }}
                </span>
            @endif
        </label>

        <label class="text-sm">
            <span class="text-stone-500 dark:text-muted-foreground">{{ __('seller.unit') }}</span>
            @php($currentUnit = old('unit', $listing->unit ?? $priceCap->unit ?? 'piece'))
            <select name="unit" required
                    class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
                @foreach(['piece', 'quintal', 'kg', 'litre', 'bottle', 'person', 'sac'] as $unit)
                    <option value="{{ $unit }}" @selected($currentUnit === $unit)>{{ __('unit.'.$unit) }}</option>
                @endforeach
            </select>
        </label>

        <label class="text-sm" x-show="tracksStock()" x-cloak>
            <span class="text-stone-500 dark:text-muted-foreground">{{ __('seller.stock') }}</span>
            <input name="stock" type="number" min="0" value="{{ old('stock', $listing->stock ?? 0) }}"
                   class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
        </label>

        <label class="text-sm">
            <span class="text-stone-500 dark:text-muted-foreground">{{ __('seller.min_order_qty') }}</span>
            <input name="min_order_qty" type="number" min="1" value="{{ old('min_order_qty', $listing->min_order_qty ?? 1) }}"
                   class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
            <span class="mt-1 block text-xs text-stone-400 dark:text-muted-foreground">{{ __('seller.min_order_qty_help') }}</span>
        </label>

        <label class="text-sm">
            <span class="text-stone-500 dark:text-muted-foreground">{{ __('seller.availability') }}</span>
            <select name="availability_model" x-model="availability" required
                    class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
                @foreach(\App\Enums\AvailabilityModel::cases() as $model)
                    <option value="{{ $model->value }}">{{ $model->label() }}</option>
                @endforeach
            </select>
        </label>

        <label class="text-sm">
            <span class="text-stone-500 dark:text-muted-foreground">{{ __('seller.lead_time_days') }}</span>
            <input name="lead_time_days" type="number" min="0" value="{{ old('lead_time_days', $listing->lead_time_days ?? 0) }}"
                   class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
            <span class="mt-1 block text-xs text-stone-400 dark:text-muted-foreground">{{ __('seller.lead_time_days_help') }}</span>
        </label>

        <label class="text-sm" x-show="availability === 'seasonal'" x-cloak>
            <span class="text-stone-500 dark:text-muted-foreground">{{ __('seller.season_start') }}</span>
            <input name="season_start" type="date"
                   value="{{ old('season_start', optional($listing?->season_start)->toDateString()) }}"
                   class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
        </label>

        <label class="text-sm" x-show="availability === 'seasonal'" x-cloak>
            <span class="text-stone-500 dark:text-muted-foreground">{{ __('seller.season_end') }}</span>
            <input name="season_end" type="date"
                   value="{{ old('season_end', optional($listing?->season_end)->toDateString()) }}"
                   class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
        </label>

        @if($rule?->requires_lot_number)
            <label class="text-sm sm:col-span-2">
                <span class="text-stone-500 dark:text-muted-foreground">{{ __('seller.lot_number') }} *</span>
                <input name="lot_number" required value="{{ old('lot_number', $listing->lot_number ?? '') }}"
                       class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
                <span class="mt-1 block text-xs text-stone-500 dark:text-muted-foreground">{{ __('seller.lot_number_help') }}</span>
            </label>
        @endif
    </div>
</section>

{{-- FR-012 — this whole block is generated from the category. No PHP here
     knows what wheat seed or rose water is. --}}
@if($fields->isNotEmpty())
    <section class="rounded-2xl border border-stone-200 bg-white p-5 shadow-card dark:border-border dark:bg-card">
        <h2 class="mb-4 flex items-center gap-2 font-semibold text-leaf-900 dark:text-foreground">
            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-gradient-card-tint text-xs font-bold text-leaf-700 dark:text-primary">3</span>
            {{ __('seller.category_fields') }}
        </h2>

        <div class="grid gap-4 sm:grid-cols-2">
            @foreach($fields as $field)
                @php($current = old('attributes.'.$field->key, $listing?->attr($field->key)))
                <label class="text-sm">
                    <span class="text-stone-500 dark:text-muted-foreground">
                        {{ $field->name() }}@if($field->required) *@endif
                        @if($field->unit) <span class="text-stone-400 dark:text-muted-foreground/70">({{ $field->unit }})</span>@endif
                    </span>

                    @switch($field->data_type)
                        @case('boolean')
                            <select name="attributes[{{ $field->key }}]"
                                    class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
                                <option value="0" @selected(! $current)>{{ __('common.no') }}</option>
                                <option value="1" @selected((bool) $current)>{{ __('common.yes') }}</option>
                            </select>
                            @break

                        @case('select')
                            <select name="attributes[{{ $field->key }}]" @required($field->required)
                                    class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
                                <option value="">—</option>
                                @foreach(($field->options ?? []) as $value => $label)
                                    <option value="{{ $value }}" @selected((string) $current === (string) $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @break

                        @case('text')
                            <textarea name="attributes[{{ $field->key }}]" rows="3" @required($field->required)
                                      class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">{{ $current }}</textarea>
                            @break

                        @case('number')
                        @case('integer')
                            <input type="number" step="{{ $field->data_type === 'integer' ? '1' : 'any' }}"
                                   name="attributes[{{ $field->key }}]" @required($field->required) value="{{ $current }}"
                                   class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
                            @break

                        @case('date')
                            <input type="date" name="attributes[{{ $field->key }}]" @required($field->required) value="{{ $current }}"
                                   class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
                            @break

                        @default
                            <input name="attributes[{{ $field->key }}]" @required($field->required) value="{{ $current }}"
                                   class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
                    @endswitch

                    @if($field->translate('help'))
                        <span class="mt-1 block text-xs text-stone-400 dark:text-muted-foreground/70">{{ $field->translate('help') }}</span>
                    @endif
                </label>
            @endforeach
        </div>
    </section>
@endif

<section class="rounded-2xl border border-stone-200 bg-white p-5 shadow-card dark:border-border dark:bg-card">
    <h2 class="mb-1 flex items-center gap-2 font-semibold text-leaf-900 dark:text-foreground">
        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-gradient-card-tint text-xs font-bold text-leaf-700 dark:text-primary">{{ $fields->isNotEmpty() ? 4 : 3 }}</span>
        {{ __('seller.green_attributes') }}
    </h2>
    <p class="mb-4 text-xs text-stone-500 dark:text-muted-foreground">{{ __('seller.green_attributes_help') }}</p>

    @php($checkedGreen = collect(old('green', $selectedGreen ?? [])))

    <div class="flex flex-wrap gap-2">
        @foreach($greenAttributes as $attribute)
            <label class="flex cursor-pointer items-center gap-2 rounded-full border border-stone-200 bg-stone-50 px-3 py-1.5 text-sm text-stone-600 transition
                          hover:border-leaf-300 has-[:checked]:border-primary has-[:checked]:bg-primary/10 has-[:checked]:text-leaf-800
                          dark:border-border dark:bg-muted dark:text-muted-foreground dark:hover:border-primary/50
                          dark:has-[:checked]:border-primary dark:has-[:checked]:bg-primary/15 dark:has-[:checked]:text-primary">
                <input type="checkbox" name="green[]" value="{{ $attribute->code }}"
                       @checked($checkedGreen->contains($attribute->code)) class="sr-only">
                @if($attribute->icon)
                    <span aria-hidden="true">{{ $attribute->icon }}</span>
                @endif
                <span>{{ $attribute->name() }}</span>
            </label>
        @endforeach
    </div>
</section>

{{-- Phase 2 §9 — product storytelling. Every field is optional and shown
     on the product page only if the seller actually filled it in. --}}
<section class="rounded-2xl border border-stone-200 bg-white p-5 shadow-card dark:border-border dark:bg-card">
    <h2 class="mb-1 flex items-center gap-2 font-semibold text-leaf-900 dark:text-foreground">
        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-gradient-card-tint text-xs font-bold text-leaf-700 dark:text-primary">{{ $fields->isNotEmpty() ? 5 : 4 }}</span>
        {{ __('seller.section_story') }}
    </h2>
    <p class="mb-4 text-xs text-stone-500 dark:text-muted-foreground">{{ __('seller.section_story_help') }}</p>

    <div class="space-y-4">
        <label class="block text-sm">
            <span class="text-stone-500 dark:text-muted-foreground">{{ __('seller.origin_locality') }}</span>
            <input name="origin_locality" value="{{ old('origin_locality', $listing?->origin_locality) }}"
                   class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring">
        </label>

        <label class="block text-sm">
            <span class="text-stone-500 dark:text-muted-foreground">{{ __('seller.story') }}</span>
            <textarea name="story[{{ app()->getLocale() }}]" rows="3"
                      class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring"
                      >{{ old('story.'.app()->getLocale(), data_get($listing?->story, app()->getLocale())) }}</textarea>
        </label>

        <label class="block text-sm">
            <span class="text-stone-500 dark:text-muted-foreground">{{ __('seller.production_process') }}</span>
            <span class="mt-1 block text-xs text-stone-400 dark:text-muted-foreground/70">{{ __('seller.production_process_help') }}</span>
            <textarea name="production_process" rows="4"
                      class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring"
                      >{{ old('production_process', implode("\n", $listing?->production_process[app()->getLocale()] ?? [])) }}</textarea>
        </label>

        <label class="block text-sm">
            <span class="text-stone-500 dark:text-muted-foreground">{{ __('seller.ingredients_materials') }}</span>
            <textarea name="ingredients_materials[{{ app()->getLocale() }}]" rows="2"
                      class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring"
                      >{{ old('ingredients_materials.'.app()->getLocale(), data_get($listing?->ingredients_materials, app()->getLocale())) }}</textarea>
        </label>

        <label class="block text-sm">
            <span class="text-stone-500 dark:text-muted-foreground">{{ __('seller.packaging_info') }}</span>
            <textarea name="packaging_info[{{ app()->getLocale() }}]" rows="2"
                      class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring"
                      >{{ old('packaging_info.'.app()->getLocale(), data_get($listing?->packaging_info, app()->getLocale())) }}</textarea>
        </label>

        <label class="block text-sm">
            <span class="text-stone-500 dark:text-muted-foreground">{{ __('seller.care_instructions') }}</span>
            <textarea name="care_instructions[{{ app()->getLocale() }}]" rows="2"
                      class="mt-1 w-full rounded-lg border-stone-300 text-sm focus:border-primary focus:ring-primary dark:border-input dark:bg-muted dark:text-foreground dark:focus:ring-ring"
                      >{{ old('care_instructions.'.app()->getLocale(), data_get($listing?->care_instructions, app()->getLocale())) }}</textarea>
        </label>
    </div>
</section>
