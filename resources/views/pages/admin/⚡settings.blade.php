<?php

declare(strict_types=1);

use App\Http\Requests\Admin\SettingRequest;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new
#[Layout('components.admin.layout')]
class extends Component
{
    use WithFileUploads;

    /**
     * The single settings row, mirrored into flat properties so each box binds
     * to one of them.
     *
     * @var array<string, string>
     */
    public array $form = [];

    public ?string $logoPath = null;

    /** @var TemporaryUploadedFile|null */
    public $logo = null;

    /**
     * Every column the screen offers. There is deliberately no colour here:
     * the palette is a fixed token set in DESIGN.md, not club data, so this
     * screen must never grow a colour picker.
     *
     * @var list<string>
     */
    private const FIELDS = [
        'club_name_ar', 'club_name_en',
        'tagline_ar', 'tagline_en',
        'description_ar', 'description_en',
        'address_ar', 'address_en',
        'city_ar', 'city_en',
        'map_url', 'phone', 'whatsapp', 'instagram', 'email',
    ];

    public function mount(): void
    {
        $setting = $this->setting();

        $this->authorize('update', $setting);

        foreach (self::FIELDS as $field) {
            $this->form[$field] = (string) ($setting->{$field} ?? '');
        }

        $this->logoPath = $setting->logo_path;
    }

    public function save(): void
    {
        $setting = $this->setting();

        $this->authorize('update', $setting);

        $rules = [];

        foreach (SettingRequest::rulesFor() as $field => $rule) {
            $rules[$field === 'logo' ? 'logo' : 'form.'.$field] = $rule;
        }

        $names = [];

        foreach (SettingRequest::attributeNames() as $field => $name) {
            $names[$field === 'logo' ? 'logo' : 'form.'.$field] = $name;
        }

        $this->validate($rules, [], $names);

        $attributes = [];

        foreach (self::FIELDS as $field) {
            $attributes[$field] = blank($this->form[$field] ?? null) ? null : $this->form[$field];
        }

        // `club_name_ar` is the one column the schema will not take as null.
        $attributes['club_name_ar'] = $this->form['club_name_ar'];

        if ($this->logo instanceof TemporaryUploadedFile) {
            $previous = $setting->logo_path;

            $attributes['logo_path'] = $this->logo->store('club', 'public');

            if (filled($previous)) {
                Storage::disk('public')->delete($previous);
            }
        }

        $setting->forceFill($attributes)->save();

        /*
         * `Setting::current()` is cached, and the model already forgets that
         * cache on its own `saved` and `deleted` events — verified in
         * App\Models\Setting::booted(). Calling Setting::forget() here as well
         * would be a second, silent source of truth for cache invalidation, so
         * it is deliberately left out.
         */

        $this->logoPath = $setting->fresh()?->logo_path;
        $this->reset('logo');

        session()->flash('status', __('admin.settings.saved'));
    }

    public function removeLogo(): void
    {
        $setting = $this->setting();

        $this->authorize('update', $setting);

        if (filled($setting->logo_path)) {
            Storage::disk('public')->delete($setting->logo_path);
        }

        $setting->forceFill(['logo_path' => null])->save();

        $this->logoPath = null;

        session()->flash('status', __('admin.settings.saved'));
    }

    /**
     * A fresh install has no row yet, and the screen still has to render.
     */
    private function setting(): Setting
    {
        return Setting::query()->first() ?? new Setting;
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'logoUrl' => filled($this->logoPath) ? Storage::disk('public')->url($this->logoPath) : null,
        ];
    }
};
?>

<div>
    <x-admin.page-header :title="__('admin.settings.title')" :description="__('admin.settings.intro')"/>

    <form wire:submit="save" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="flex flex-col gap-5">
            <x-ui.card class="grid gap-5 sm:grid-cols-2">
                <x-ui.field :label="__('admin.settings.club_name_ar')" id="setting-club-name-ar" required
                            wire:model="form.club_name_ar" :error="$errors->first('form.club_name_ar')"/>

                <x-ui.field :label="__('admin.settings.club_name_en')" id="setting-club-name-en" dir="ltr"
                            wire:model="form.club_name_en" :error="$errors->first('form.club_name_en')"/>

                <x-ui.field :label="__('admin.settings.tagline_ar')" id="setting-tagline-ar"
                            wire:model="form.tagline_ar" :error="$errors->first('form.tagline_ar')"/>

                <x-ui.field :label="__('admin.settings.tagline_en')" id="setting-tagline-en" dir="ltr"
                            wire:model="form.tagline_en" :error="$errors->first('form.tagline_en')"/>

                <x-ui.field class="sm:col-span-2" :label="__('admin.settings.description_ar')" id="setting-description-ar"
                            :error="$errors->first('form.description_ar')">
                    <x-admin.textarea id="setting-description-ar" rows="4" wire:model="form.description_ar"
                                      :error="filled($errors->first('form.description_ar'))"/>
                </x-ui.field>

                <x-ui.field class="sm:col-span-2" :label="__('admin.settings.description_en')" id="setting-description-en"
                            :error="$errors->first('form.description_en')">
                    <x-admin.textarea id="setting-description-en" rows="4" dir="ltr" wire:model="form.description_en"
                                      :error="filled($errors->first('form.description_en'))"/>
                </x-ui.field>
            </x-ui.card>

            <x-ui.card class="grid gap-5 sm:grid-cols-2">
                <x-ui.field :label="__('admin.settings.address_ar')" id="setting-address-ar"
                            wire:model="form.address_ar" :error="$errors->first('form.address_ar')"/>

                <x-ui.field :label="__('admin.settings.address_en')" id="setting-address-en" dir="ltr"
                            wire:model="form.address_en" :error="$errors->first('form.address_en')"/>

                <x-ui.field :label="__('admin.settings.city_ar')" id="setting-city-ar"
                            wire:model="form.city_ar" :error="$errors->first('form.city_ar')"/>

                <x-ui.field :label="__('admin.settings.city_en')" id="setting-city-en" dir="ltr"
                            wire:model="form.city_en" :error="$errors->first('form.city_en')"/>

                <x-ui.field class="sm:col-span-2" :label="__('admin.settings.map_url')" id="setting-map-url" type="url" dir="ltr"
                            wire:model="form.map_url" :error="$errors->first('form.map_url')"/>
            </x-ui.card>

            <x-ui.card class="grid gap-5 sm:grid-cols-2">
                <x-ui.field :label="__('admin.settings.phone')" id="setting-phone" type="tel" dir="ltr"
                            wire:model="form.phone" :error="$errors->first('form.phone')"/>

                <x-ui.field :label="__('admin.settings.whatsapp')" id="setting-whatsapp" type="tel" dir="ltr"
                            wire:model="form.whatsapp" :error="$errors->first('form.whatsapp')"/>

                <x-ui.field :label="__('admin.settings.instagram')" id="setting-instagram" dir="ltr"
                            wire:model="form.instagram" :error="$errors->first('form.instagram')"/>

                <x-ui.field :label="__('admin.settings.email')" id="setting-email" type="email" dir="ltr"
                            wire:model="form.email" :error="$errors->first('form.email')"/>
            </x-ui.card>
        </div>

        <aside class="flex flex-col gap-5">
            <x-ui.card class="flex flex-col gap-4">
                <h3 class="text-lg font-semibold text-ink-50">{{ __('admin.settings.logo') }}</h3>

                @if ($logoUrl)
                    {{-- DESIGN.md §7: the club logo sits on ink-900 or ink-950 only. --}}
                    <div class="flex items-center justify-center rounded-md border border-ink-700 bg-ink-950 p-5">
                        <img src="{{ $logoUrl }}" alt="{{ __('admin.settings.logo') }}" class="h-16 w-auto object-contain">
                    </div>

                    <x-ui.button variant="ghost" wire:click="removeLogo">{{ __('admin.settings.logo_remove') }}</x-ui.button>
                @endif

                <x-ui.field :label="$logoUrl ? __('admin.settings.logo_replace') : __('admin.settings.logo')"
                            id="setting-logo" type="file"
                            accept="image/png,image/jpeg,image/webp"
                            :hint="__('admin.settings.logo_hint')"
                            wire:model="logo" :error="$errors->first('logo')"/>
            </x-ui.card>
        </aside>

        <x-admin.form-actions class="lg:col-span-2">
            <x-ui.button type="submit" class="flex-1 sm:flex-none">{{ __('admin.actions.update') }}</x-ui.button>
        </x-admin.form-actions>
    </form>
</div>
