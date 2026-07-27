<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Http\Requests\Admin\TraineeRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('components.admin.layout')]
class extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $role = '';

    #[Url(except: '')]
    public string $status = '';

    public bool $inviting = false;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $newRole = 'trainee';

    public string $locale = 'ar';

    public string $password = '';

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'role', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function invite(): void
    {
        $this->authorize('create', User::class);

        $rules = TraineeRequest::rulesFor();
        // The panel's own field is `newRole`: `role` is already taken by the
        // list filter above it.
        $rules['newRole'] = $rules['role'];
        unset($rules['role']);

        $names = TraineeRequest::attributeNames();
        $names['newRole'] = $names['role'];

        $validated = $this->validate($rules, [], $names);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => blank($validated['phone'] ?? null) ? null : $validated['phone'],
            'role' => $validated['newRole'],
            'locale' => $validated['locale'],
            'password' => $validated['password'],
            'is_active' => true,
        ]);

        $this->reset('inviting', 'name', 'email', 'phone', 'newRole', 'locale', 'password');

        unset($this->trainees);

        session()->flash('status', __('admin.messages.created', ['name' => $user->name]));
    }

    public function toggleActive(int $id): void
    {
        $user = User::query()->findOrFail($id);

        $this->authorize('deactivate', $user);

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        unset($this->trainees);

        session()->flash('status', __(
            $user->is_active ? 'admin.messages.activated' : 'admin.messages.deactivated',
            ['name' => $user->name],
        ));
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    #[Computed]
    public function trainees(): LengthAwarePaginator
    {
        return User::query()
            ->when(filled($this->search), function (Builder $query): void {
                $term = '%'.$this->search.'%';

                $query->where(fn (Builder $inner) => $inner
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term));
            })
            ->when(filled($this->role), fn (Builder $query) => $query->where('role', $this->role))
            ->when($this->status === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($this->status === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(20);
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'roles' => UserRole::cases(),
            'locales' => (array) config('areen.locales'),
        ];
    }
};
?>

<div>
    <x-admin.page-header :title="__('admin.trainees.title')" :description="__('admin.trainees.invite_hint')">
        @can('create', App\Models\User::class)
            <x-slot:actions>
                <x-ui.button wire:click="$toggle('inviting')">
                    <x-admin.icon name="plus" class="size-5"/>
                    {{ __('admin.trainees.add') }}
                </x-ui.button>
            </x-slot:actions>
        @endcan
    </x-admin.page-header>

    @if ($inviting)
        <form wire:submit="invite" class="mb-6">
            <x-ui.card class="grid gap-5 sm:grid-cols-2">
                <x-ui.field :label="__('auth.fields.name')" id="invite-name" required
                            wire:model="name" :error="$errors->first('name')"/>

                <x-ui.field :label="__('auth.fields.email')" id="invite-email" type="email" dir="ltr" required
                            wire:model="email" :error="$errors->first('email')"/>

                <x-ui.field :label="__('auth.fields.phone')" id="invite-phone" type="tel" dir="ltr"
                            wire:model="phone" :error="$errors->first('phone')"/>

                <x-ui.field :label="__('admin.trainees.role')" id="invite-role" :error="$errors->first('newRole')">
                    <x-admin.select id="invite-role" wire:model="newRole" :error="filled($errors->first('newRole'))">
                        @foreach ($roles as $case)
                            <option wire:key="role-{{ $case->value }}" value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </x-admin.select>
                </x-ui.field>

                <x-ui.field :label="__('auth.account.language')" id="invite-locale" :error="$errors->first('locale')">
                    <x-admin.select id="invite-locale" wire:model="locale" :error="filled($errors->first('locale'))">
                        @foreach ($locales as $code => $entry)
                            <option wire:key="locale-{{ $code }}" value="{{ $code }}">{{ $entry['name'] ?? $code }}</option>
                        @endforeach
                    </x-admin.select>
                </x-ui.field>

                <x-ui.field :label="__('auth.fields.password')" id="invite-password" type="text" dir="ltr" required
                            :hint="__('admin.trainees.password_hint')"
                            wire:model="password" :error="$errors->first('password')"/>

                <div class="flex flex-wrap gap-2 sm:col-span-2">
                    <x-ui.button type="submit">{{ __('admin.actions.store') }}</x-ui.button>
                    <x-ui.button variant="ghost" wire:click="$toggle('inviting')">{{ __('common.actions.cancel') }}</x-ui.button>
                </div>
            </x-ui.card>
        </form>
    @endif

    @island(name: 'trainees')
        <div>
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
                <x-ui.field class="flex-1 sm:min-w-56" id="trainees-search"
                            :label="__('admin.table.search')" type="search"
                            wire:model.live.debounce.300ms="search"/>

                <x-ui.field class="sm:w-44" id="trainees-role" :label="__('admin.trainees.role')">
                    <x-admin.select id="trainees-role" wire:model.live="role">
                        <option value="">{{ __('admin.filters.all_roles') }}</option>
                        @foreach ($roles as $case)
                            <option wire:key="role-{{ $case->value }}" value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </x-admin.select>
                </x-ui.field>

                <x-ui.field class="sm:w-44" id="trainees-status" :label="__('admin.trainees.status')">
                    <x-admin.select id="trainees-status" wire:model.live="status">
                        <option value="">{{ __('admin.filters.all_status') }}</option>
                        <option value="active">{{ __('admin.trainees.active') }}</option>
                        <option value="inactive">{{ __('admin.trainees.inactive') }}</option>
                    </x-admin.select>
                </x-ui.field>
            </div>

            @if ($this->trainees->isEmpty())
                <x-ui.empty-state>
                    <x-slot:title>{{ filled($search) || filled($role) || filled($status) ? __('admin.empty.results_title') : __('admin.empty.trainees_title') }}</x-slot:title>
                    <x-slot:body>{{ filled($search) || filled($role) || filled($status) ? __('admin.empty.results_body') : __('admin.empty.trainees_body') }}</x-slot:body>
                </x-ui.empty-state>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-start text-sm">
                        <thead>
                            <tr class="border-b border-ink-700 text-xs font-medium text-ink-400">
                                <th scope="col" class="py-3 pe-3 text-start">{{ __('auth.fields.name') }}</th>
                                <th scope="col" class="hidden py-3 pe-3 text-start md:table-cell">{{ __('auth.fields.email') }}</th>
                                <th scope="col" class="hidden py-3 pe-3 text-start sm:table-cell">{{ __('admin.trainees.role') }}</th>
                                <th scope="col" class="hidden py-3 pe-3 text-start sm:table-cell">{{ __('admin.trainees.status') }}</th>
                                <th scope="col" class="py-3 text-end">{{ __('admin.actions.edit') }}</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($this->trainees as $person)
                                <tr wire:key="person-{{ $person->id }}" class="border-b border-ink-800 transition-colors duration-150 ease-out hover:bg-ink-800/50">
                                    <td class="py-3 pe-3">
                                        <a href="{{ route('admin.trainees.show', $person) }}"
                                           wire:navigate
                                           class="block font-medium text-ink-100">{{ $person->name }}</a>
                                        <span class="block text-xs text-ink-400 md:hidden" dir="ltr">{{ $person->email }}</span>
                                    </td>

                                    <td class="hidden py-3 pe-3 text-ink-300 md:table-cell" dir="ltr">{{ $person->email }}</td>

                                    <td class="hidden py-3 pe-3 text-ink-300 sm:table-cell">{{ $person->role->label() }}</td>

                                    <td class="hidden py-3 pe-3 sm:table-cell">
                                        <x-ui.chip :active="$person->is_active">
                                            {{ $person->is_active ? __('admin.trainees.active') : __('admin.trainees.inactive') }}
                                        </x-ui.chip>
                                    </td>

                                    <td class="py-3">
                                        <div class="flex items-center justify-end gap-1">
                                            @can('deactivate', $person)
                                                <button type="button"
                                                        wire:click="toggleActive({{ $person->id }})"
                                                        class="inline-flex min-h-11 items-center rounded-sm px-3 text-sm font-medium text-ink-200 hover:bg-ink-800">
                                                    {{ $person->is_active ? __('admin.trainees.deactivate') : __('admin.trainees.activate') }}
                                                </button>
                                            @endcan

                                            <a href="{{ route('admin.trainees.show', $person) }}"
                                               wire:navigate
                                               aria-label="{{ __('admin.actions.edit') }}"
                                               class="inline-flex size-11 items-center justify-center rounded-sm text-ink-200 hover:bg-ink-800">
                                                <x-admin.icon name="pencil" class="size-5"/>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $this->trainees->links() }}</div>
            @endif
        </div>
    @endisland
</div>
