<?php

use App\Models\Program;
use App\Support\ProgramAccess;
use Livewire\Component;

new class extends Component
{
    public Program $program;

    /**
     * The coded door. The access code is the credential, so this page looks the
     * program up by that code alone and deliberately does not ask whether it is
     * published — a private program is reachable here and nowhere else.
     *
     * `whereNotNull` keeps a program that was never given a code out of reach of
     * a request that arrives with a blank one.
     */
    public function mount(string $accessCode): void
    {
        $this->program = Program::query()
            ->whereNotNull('access_code')
            ->where('access_code', $accessCode)
            ->firstOrFail();

        /*
         * Opening this door unlocks the program's day pages for the rest of the
         * session, so every link from here can carry the slug alone and the code
         * never has to travel into a href.
         */
        ProgramAccess::grant(request(), $this->program);
    }
};
?>

@section('title', $program->name.' · '.__('common.app_name'))

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

{{--
  Rendered from the same component as `programs.show`: the two doors show one
  overview, so they cannot drift apart. Every link out of here carries the
  program slug only — the access code stays in the address bar and never travels
  into a href, a referrer or a shared screenshot of a button.
--}}
<div>
    <x-program.overview :program="$program"/>
</div>
