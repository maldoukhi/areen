@extends('layouts.app')

@section('title', __('common.app_name'))

@section('content')
    <div class="flex min-h-dvh flex-col items-center justify-center gap-6 px-4 text-center safe-pt safe-pb">
        <x-brand.mark class="size-16 text-brand-400"/>

        <div class="space-y-2">
            <h1 class="text-[2rem] font-bold text-ink-50">{{ __('common.app_name') }}</h1>
            <p class="text-ink-300">{{ __('common.tagline') }}</p>
        </div>
    </div>
@endsection
