{{--
  One line, above the page, for what just happened. Livewire actions announce
  through `session()->flash('status', …)`; the redirect after a save carries the
  same key, so both paths land in the same place.

  `role="status"` rather than an alert: a saved record is not an emergency.
--}}

@if (session()->has('status') || session()->has('danger'))
    @php
        $isError = session()->has('danger');
        $message = $isError ? session('danger') : session('status');
    @endphp

    <div role="status"
         @class([
             'mb-4 flex items-start gap-3 rounded-md border px-4 py-3 text-sm',
             'border-danger/40 bg-danger/10 text-danger' => $isError,
             'border-success/40 bg-success/10 text-success' => ! $isError,
         ])>
        <x-admin.icon :name="$isError ? 'alert' : 'check'" class="mt-0.5 size-5 shrink-0"/>
        <p class="leading-normal">{{ $message }}</p>
    </div>
@endif
