<?php

namespace App\Models\Concerns;

/**
 * Minimal translatable-attribute support with no external package.
 *
 * Columns declared in $translatable are JSON maps: {"ar": "...", "fr": "..."}.
 * Reading returns the active locale, falling back to the configured fallback
 * and then to the first non-empty value — so a half-translated catalogue
 * still renders something rather than a blank.
 */
trait HasTranslations
{
    public function translate(string $attribute, ?string $locale = null): ?string
    {
        $values = $this->getAttribute($attribute);

        if (is_string($values)) {
            $decoded = json_decode($values, true);
            $values = is_array($decoded) ? $decoded : [$locale ?? app()->getLocale() => $values];
        }

        if (! is_array($values) || $values === []) {
            return null;
        }

        $locale ??= app()->getLocale();

        foreach ([$locale, config('app.fallback_locale'), 'fr', 'ar', 'en'] as $candidate) {
            if (! empty($values[$candidate])) {
                return $values[$candidate];
            }
        }

        $first = collect($values)->filter()->first();

        return is_string($first) ? $first : null;
    }

    /** Set one locale without clobbering the others. */
    public function setTranslation(string $attribute, string $locale, ?string $value): static
    {
        $values = $this->getAttribute($attribute);
        $values = is_array($values) ? $values : [];
        $values[$locale] = $value;
        $this->setAttribute($attribute, $values);

        return $this;
    }

    public function initializeHasTranslations(): void
    {
        foreach ($this->translatable ?? [] as $attribute) {
            $this->casts[$attribute] = 'array';
        }
    }
}
