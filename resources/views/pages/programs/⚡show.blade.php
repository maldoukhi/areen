<?php

use App\Models\Program;
use Livewire\Component;

new class extends Component
{
    public Program $program;

    /**
     * The public door. It re-asks the catalogue's own `published()` question
     * instead of restating the rule, so a program that is unpublished, private
     * or scheduled for later is a 404 here even to someone who guessed the slug.
     */
    public function mount(Program $program): void
    {
        abort_unless(
            Program::query()->published()->whereKey($program->getKey())->exists(),
            404,
        );

        $this->program = $program;
    }
};
?>

@section('title', $program->name.' · '.__('common.app_name'))

@push('head')
    {{-- `article`, not `website`: a plan is a document with an author and a date. --}}
    <x-seo.page :description="$program->description" type="article"/>
@endpush

<div>
    <x-program.overview :program="$program"/>
</div>
