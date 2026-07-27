<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Exercise;
use App\Models\Program;
use App\Models\User;
use App\Models\WorkoutLog;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('components.admin.layout')]
class extends Component
{
    /**
     * Four numbers that change what somebody does next, and nothing else. A
     * chart here would be decoration (DESIGN.md §1).
     *
     * @return array<string, mixed>
     */
    public function with(): array
    {
        /*
         * The Saudi training week runs Sunday to Thursday, so "this week" is
         * counted from Sunday rather than from Carbon's Monday default.
         */
        $weekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY);

        return [
            'programCount' => Program::query()->count(),
            'exerciseCount' => Exercise::query()->active()->count(),
            'traineeCount' => User::query()->where('role', UserRole::Trainee)->where('is_active', true)->count(),
            'setsThisWeek' => WorkoutLog::query()
                ->whereBetween('performed_on', [$weekStart->toDateString(), $weekStart->copy()->addDays(6)->toDateString()])
                ->count(),
            'draftCount' => Program::query()->where('is_public', false)->count(),
        ];
    }
};
?>

<div>
    <x-admin.page-header :title="__('admin.dashboard')" :description="__('admin.dashboard_intro')"/>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-ui.card>
            <x-ui.metric size="lg" :value="$programCount" :caption="__('admin.stats.programs')"/>
        </x-ui.card>

        <x-ui.card>
            <x-ui.metric size="lg" :value="$exerciseCount" :caption="__('admin.stats.exercises')"/>
        </x-ui.card>

        <x-ui.card>
            <x-ui.metric size="lg" :value="$traineeCount" :caption="__('admin.stats.trainees')"/>
        </x-ui.card>

        {{-- The single ember on this screen, per DESIGN.md §2: the number that moves. --}}
        <x-ui.card>
            <x-ui.metric size="lg" tone="ember" :value="$setsThisWeek" :caption="__('admin.stats.sets_this_week')"/>
        </x-ui.card>
    </div>

    <div class="mt-8 grid gap-3 sm:grid-cols-2">
        @can('create', Program::class)
            <x-ui.card :href="route('admin.programs.create')" class="flex items-center gap-3">
                <x-admin.icon name="plus" class="size-5 shrink-0 text-brand-400"/>
                <span class="text-base font-medium text-ink-100">{{ __('admin.empty.programs_title') }}</span>
            </x-ui.card>
        @endcan

        @can('create', Exercise::class)
            <x-ui.card :href="route('admin.exercises.create')" class="flex items-center gap-3">
                <x-admin.icon name="plus" class="size-5 shrink-0 text-brand-400"/>
                <span class="text-base font-medium text-ink-100">{{ __('exercise.empty.action') }}</span>
            </x-ui.card>
        @endcan
    </div>

    @if ($draftCount > 0)
        <p class="mt-6 text-sm text-ink-400">
            {{ __('program.visibility.draft') }}: <span class="tabular text-ink-200">{{ $draftCount }}</span>
        </p>
    @endif
</div>
