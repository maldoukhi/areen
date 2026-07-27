<?php

declare(strict_types=1);

namespace App\Support\Concerns;

/**
 * Exposes a bare `name` on models whose table stores `name_ar` / `name_en`.
 *
 * Models opt in with `protected array $translatable = ['name', 'description'];`
 */
trait HasTranslatableAttributes
{
    /**
     * Every row is guaranteed to carry Arabic; the English column is optional.
     * The fallback is therefore fixed to `ar` rather than read from
     * `app.fallback_locale`, which a deployment is free to point elsewhere and
     * which would then resolve to a column that does not exist.
     */
    protected const string TRANSLATION_FALLBACK_LOCALE = 'ar';

    /**
     * `getAttribute` is overridden rather than `__get` because Eloquent routes
     * array access, `toArray`, `data_get` and Blade all through it, so this is
     * the single point that covers every read.
     *
     * The translatable check runs first: a key like `name` is not a real
     * column, and letting Eloquent resolve it would hand it to
     * `throwMissingAttributeExceptionIfApplicable()` — under
     * `Model::preventAccessingMissingAttributes()` that throws instead of
     * returning null. Real attributes never reach the branch, since a key is
     * only treated as translatable while no column of that name is loaded.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (is_string($key) && $this->isTranslatableAttribute($key)) {
            return $this->translate($key);
        }

        return parent::getAttribute($key);
    }

    /**
     * Resolve a translatable key explicitly, e.g. `$exercise->translate('name')`.
     */
    public function translate(string $key): ?string
    {
        return $this->translatedValue($key, app()->getLocale())
            ?? $this->translatedValue($key, self::TRANSLATION_FALLBACK_LOCALE);
    }

    /**
     * @return list<string>
     */
    public function translatableAttributes(): array
    {
        return $this->translatable ?? [];
    }

    protected function isTranslatableAttribute(string $key): bool
    {
        return in_array($key, $this->translatableAttributes(), true)
            && ! array_key_exists($key, $this->attributes);
    }

    /**
     * An empty string counts as a missing translation, so a row saved with a
     * blank English name still renders its Arabic one.
     */
    protected function translatedValue(string $key, string $locale): ?string
    {
        $column = $key.'_'.$locale;

        if (! array_key_exists($column, $this->attributes)) {
            return null;
        }

        $value = parent::getAttribute($column);

        return filled($value) ? (string) $value : null;
    }
}
