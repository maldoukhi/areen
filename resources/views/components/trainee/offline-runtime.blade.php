{{--
  The offline queue's runtime, and the two facts it needs from the server.

  Inlined rather than loaded from `/build`: this code has to run on a page the
  service worker served from cache with no network at all, and Workbox caches
  HTML navigations and precached build assets — a `<script src>` fetched at
  runtime is neither, so it would 404 in exactly the basement this feature
  exists for. Travelling inside the HTML makes it impossible for the page to
  exist without its behaviour. See App\Actions\Trainee\OfflineRuntimeScript.

  The endpoint and the CSRF token are handed over through data attributes so the
  JavaScript knows nothing about Laravel's route names, and a page that does not
  render this component simply never drains.

      <x-trainee.offline-runtime/>

  `wire:ignore` keeps Livewire's morph away from the script tag: re-inserting it
  during a DOM patch would re-run the whole runtime for no reason.
--}}

@once
    <div data-areen-sync
         data-endpoint="{{ route('dashboard.logs.sync') }}"
         data-csrf="{{ csrf_token() }}"
         wire:ignore
         hidden>
        {{--
          Not `type="module"`: the three files under resources/js/offline are
          plain scripts precisely so they can be concatenated into one tag, and
          each guards itself against a second execution after a wire:navigate
          body swap.
        --}}
        <script data-navigate-once>{!! \App\Actions\Trainee\OfflineRuntimeScript::contents() !!}</script>
    </div>
@endonce
