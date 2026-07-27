<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Http\Requests\Admin\AssignProgramRequest;
use App\Http\Requests\Admin\TraineeRequest;
use App\Models\Program;
use App\Models\User;
use App\Models\WorkoutLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('components.admin.layout')]
class extends Component
{
    public User $trainee;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $role = 'trainee';

    public string $locale = 'ar';

    public string $password = '';

    public string $program_id = '';

    public string $started_at = '';

    public function mount(User $user): void
    {
        $this->authorize('view', $user);

        $this->trainee = $user;

        $active = $user->activeProgram();

        $this->fill([
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'phone' => (string) $user->phone,
            'role' => $user->role->value,
            'locale' => (string) $user->locale,
            'program_id' => (string) ($active?->getKey() ?? ''),
            'started_at' => (string) ($active?->pivot?->started_at ?? now()->toDateString()),
        ]);
    }

    public function save(): void
    {
        $this->authorize('update', $this->trainee);

        $validated = $this->validate(
            TraineeRequest::rulesFor($this->trainee),
            [],
            TraineeRequest::attributeNames(),
        );

        $this->trainee->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => blank($validated['phone'] ?? null) ? null : $validated['phone'],
            'role' => $validated['role'],
            'locale' => $validated['locale'],
        ]);

        // An empty box means "leave the password alone", never "blank it".
        if (filled($this->password)) {
            $this->trainee->password = $this->password;
        }

        $this->trainee->save();

        $this->reset('password');

        session()->flash('status', __('admin.messages.updated'));
    }

    public function toggleActive(): void
    {
        $this->authorize('deactivate', $this->trainee);

        $this->trainee->forceFill(['is_active' => ! $this->trainee->is_active])->save();

        session()->flash('status', __(
            $this->trainee->is_active ? 'admin.messages.activated' : 'admin.messages.deactivated',
            ['name' => $this->trainee->name],
        ));
    }

    /**
     * A trainee follows one program at a time, so assigning a new one stands the
     * previous ones down rather than leaving two live plans on the same account.
     */
    public function assignProgram(): void
    {
        $this->authorize('assignProgram', $this->trainee);

        $validated = $this->validate(
            AssignProgramRequest::rulesFor(),
            [],
            AssignProgramRequest::attributeNames(),
        );

        $this->trainee->programs()->newPivotQuery()->update(['is_active' => false]);

        $this->trainee->programs()->syncWithoutDetaching([
            (int) $validated['program_id'] => [
                'started_at' => $validated['started_at'],
                'is_active' => true,
            ],
        ]);

        $this->trainee = $this->trainee->fresh();

        session()->flash('status', __('admin.messages.assigned', ['name' => $this->trainee->name]));
    }

    public function unassignProgram(int $programId): void
    {
        $this->authorize('assignProgram', $this->trainee);

        $this->trainee->programs()->detach($programId);

        $this->trainee = $this->trainee->fresh();
        $this->program_id = '';

        session()->flash('status', __('admin.messages.unassigned', ['name' => $this->trainee->name]));
    }

    /**
     * The access code lives on the program, not on the member — regenerating it
     * retires every link already shared for that program, so the warning sits
     * beside the button.
     */
    public function regenerateAccessCode(int $programId): void
    {
        $program = Program::query()->findOrFail($programId);

        $this->authorize('update', $program);

        do {
            $code = Str::upper(Str::random(8));
        } while (Program::withTrashed()->where('access_code', $code)->exists());

        $program->forceFill(['access_code' => $code])->save();

        $this->trainee = $this->trainee->fresh();

        session()->flash('status', __('admin.messages.code_generated'));
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $weekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY);

        return [
            'roles' => UserRole::cases(),
            'locales' => (array) config('areen.locales'),
            'programs' => Program::query()->ordered()->get(),
            'activeProgram' => $this->trainee->activeProgram(),
            'setsThisWeek' => WorkoutLog::query()
                ->forUser($this->trainee)
                ->whereBetween('performed_on', [$weekStart->toDateString(), $weekStart->copy()->addDays(6)->toDateString()])
                ->count(),
        ];
    }
};
?>

<div>
    <x-admin.page-header :back="route('admin.trainees.index')" :title="$trainee->name">
        <x-slot:actions>
            @can('deactivate', $trainee)
                <x-ui.button variant="secondary" wire:click="toggleActive">
                    {{ $trainee->is_active ? __('admin.trainees.deactivate') : __('admin.trainees.activate') }}
                </x-ui.button>
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <form wire:submit="save" class="flex flex-col gap-5">
            <x-ui.card class="grid gap-5 sm:grid-cols-2">
                <h3 class="text-lg font-semibold text-ink-50 sm:col-span-2">{{ __('admin.trainees.account') }}</h3>

                <x-ui.field :label="__('auth.fields.name')" id="trainee-name" required
                            wire:model="name" :error="$errors->first('name')"/>

                <x-ui.field :label="__('auth.fields.email')" id="trainee-email" type="email" dir="ltr" required
                            wire:model="email" :error="$errors->first('email')"/>

                <x-ui.field :label="__('auth.fields.phone')" id="trainee-phone" type="tel" dir="ltr"
                            wire:model="phone" :error="$errors->first('phone')"/>

                <x-ui.field :label="__('admin.trainees.role')" id="trainee-role" :error="$errors->first('role')">
                    <x-admin.select id="trainee-role" wire:model="role" :error="filled($errors->first('role'))">
                        @foreach ($roles as $case)
                            <option value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </x-admin.select>
                </x-ui.field>

                <x-ui.field :label="__('auth.account.language')" id="trainee-locale" :error="$errors->first('locale')">
                    <x-admin.select id="trainee-locale" wire:model="locale" :error="filled($errors->first('locale'))">
                        @foreach ($locales as $code => $entry)
                            <option value="{{ $code }}">{{ $entry['name'] ?? $code }}</option>
                        @endforeach
                    </x-admin.select>
                </x-ui.field>

                <x-ui.field :label="__('auth.fields.new_password')" id="trainee-password" type="text" dir="ltr"
                            :hint="__('admin.trainees.password_hint')"
                            wire:model="password" :error="$errors->first('password')"/>
            </x-ui.card>

            <x-admin.form-actions>
                <x-ui.button type="submit" class="flex-1 sm:flex-none">{{ __('admin.actions.update') }}</x-ui.button>
            </x-admin.form-actions>
        </form>

        <aside class="flex flex-col gap-4">
            <x-ui.card class="flex flex-row items-center justify-between gap-4">
                <x-ui.metric size="lg" tone="ember" :value="$setsThisWeek" :caption="__('admin.trainees.sessions_this_week')"/>

                <x-ui.chip :active="$trainee->is_active">
                    {{ $trainee->is_active ? __('admin.trainees.active') : __('admin.trainees.inactive') }}
                </x-ui.chip>
            </x-ui.card>

            @can('assignProgram', $trainee)
                <form wire:submit="assignProgram">
                    <x-ui.card class="flex flex-col gap-4">
                        <h3 class="text-lg font-semibold text-ink-50">{{ __('admin.trainees.assignment') }}</h3>

                        @if ($programs->isEmpty())
                            <p class="text-sm text-ink-300">{{ __('admin.trainees.programs_none') }}</p>
                        @else
                            <x-ui.field :label="__('admin.entities.program')" id="assign-program" :error="$errors->first('program_id')">
                                <x-admin.select id="assign-program" wire:model="program_id" :error="filled($errors->first('program_id'))">
                                    <option value="">{{ __('admin.trainees.no_program') }}</option>
                                    @foreach ($programs as $program)
                                        <option value="{{ $program->id }}">{{ $program->name }}</option>
                                    @endforeach
                                </x-admin.select>
                            </x-ui.field>

                            <x-ui.field :label="__('admin.trainees.started_at')" id="assign-started" type="date"
                                        wire:model="started_at" :error="$errors->first('started_at')"/>

                            <x-ui.button type="submit" full>{{ __('admin.trainees.assign_program') }}</x-ui.button>
                        @endif

                        @if ($activeProgram)
                            <div class="flex flex-col gap-3 border-t border-ink-700 pt-4">
                                <p class="text-sm text-ink-300">
                                    {{ __('admin.trainees.active_program') }}:
                                    <a href="{{ route('admin.programs.edit', $activeProgram) }}"
                                       wire:navigate
                                       class="font-medium text-brand-400">{{ $activeProgram->name }}</a>
                                </p>

                                @if ($activeProgram->access_code)
                                    <x-admin.copy :value="route('programs.private', ['accessCode' => $activeProgram->access_code])"
                                                  :label="__('admin.access_code.link')"
                                                  :hint="__('admin.access_code.hint')"/>
                                @endif

                                <p class="text-xs leading-normal text-warning">{{ __('admin.access_code.regenerate_warning') }}</p>

                                <div class="flex flex-wrap gap-2">
                                    <x-ui.button variant="secondary"
                                                 wire:click="regenerateAccessCode({{ $activeProgram->id }})"
                                                 wire:confirm="{{ __('admin.access_code.regenerate_warning') }}">
                                        <x-admin.icon name="refresh" class="size-5"/>
                                        {{ $activeProgram->access_code ? __('admin.access_code.regenerate') : __('admin.access_code.generate') }}
                                    </x-ui.button>

                                    <x-ui.button variant="ghost" wire:click="unassignProgram({{ $activeProgram->id }})">
                                        {{ __('admin.trainees.unassign_program') }}
                                    </x-ui.button>
                                </div>
                            </div>
                        @endif
                    </x-ui.card>
                </form>
            @endcan
        </aside>
    </div>
</div>
