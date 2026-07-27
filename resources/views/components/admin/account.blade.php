@props(['user' => null])

{{--
  Who is signed in, and the way out. A real POST form, so a prefetch or an image
  tag can never sign somebody out on their behalf.
--}}

@if ($user)
    <div {{ $attributes->class('border-t border-ink-800 p-3') }}>
        <p class="px-3 text-xs font-medium leading-normal text-ink-300">{{ __('admin.shell.signed_in_as') }}</p>

        <p class="truncate px-3 pb-2 text-sm font-medium text-ink-100">{{ $user->name }}</p>

        <p class="truncate px-3 pb-3 text-xs leading-normal text-ink-300">
            {{ __('admin.trainees.role') }}: {{ $user->role->label() }}
        </p>

        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf

            <button type="submit"
                    class="flex min-h-11 w-full items-center gap-3 rounded-sm px-3 text-start text-base
                           font-medium text-ink-200 transition-colors duration-150 ease-out hover:bg-ink-800">
                <x-admin.icon name="power" class="size-5 shrink-0"/>
                <span>{{ __('auth.logout.action') }}</span>
            </button>
        </form>
    </div>
@endif
