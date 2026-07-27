@extends('layouts.app')

@section('title', __('pwa.offline.title'))

@push('head')
    {{-- Precached as a fallback for a page nobody has opened yet. It is a state,
         not a destination, and has no business in a search result. --}}
    <x-seo.page :description="__('pwa.offline.body')" :noindex="true"/>
@endpush

@section('content')
    <div class="flex min-h-dvh flex-col items-center justify-center gap-6 px-4 text-center safe-pt safe-pb">
        <x-brand.club-logo class="size-16 text-ink-600" mark-class="size-16 text-ink-600"/>

        <div class="max-w-[45ch] space-y-2">
            <h1 class="text-2xl font-bold text-ink-50">{{ __('pwa.offline.title') }}</h1>
            <p class="text-ink-300">{{ __('pwa.offline.body') }}</p>
        </div>

        <a href="{{ route('home') }}"
           class="inline-flex min-h-11 items-center rounded-sm bg-brand-400 px-[18px] py-2.5 font-medium text-brand-950">
            {{ __('common.nav.home') }}
        </a>
    </div>
@endsection
