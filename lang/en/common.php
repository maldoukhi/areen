<?php

declare(strict_types=1);

return [

    'app_name' => 'Areen',
    'tagline' => 'Your training plan, in your pocket',
    'list_separator' => ', ',

    'nav' => [
        'home' => 'Home',
        'programs' => 'Programs',
        'exercises' => 'Exercises',
        'my_workout' => 'My workout',
        'account' => 'Account',
        'about' => 'About the club',
        'admin' => 'Admin',
    ],

    'actions' => [
        'back' => 'Back',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
        'edit' => 'Edit',
        'search' => 'Search',
        'filter' => 'Filter',
        'clear' => 'Clear',
        'print' => 'Print',
        'download_pdf' => 'Download PDF',
        'retry' => 'Try again',
        'add' => 'Add',
        'open' => 'Open',
        'close' => 'Close',
        'copy' => 'Copy',
        'copied' => 'Copied',
        'share' => 'Share',
        'show_more' => 'Show more',
        'show_less' => 'Show less',
        'skip_to_content' => 'Skip to content',
    ],

    'locale' => [
        'switch' => 'Change language',
        'current' => 'Current language',
    ],

    'units' => [
        'kg' => 'kg',
        'lb' => 'lb',
        'second' => 'second|seconds',
        'minute' => 'minute|minutes',
    ],

    'states' => [
        'loading' => 'Loading',
        'error_title' => 'That did not go through',
        'error_body' => 'Something unexpected happened. Try again in a moment.',
        'not_found_title' => 'Page not found',
        'not_found_body' => 'The link you opened no longer exists. Head back home and start from there.',
        'empty_invite_title' => 'Start here',
        'empty_invite_body' => 'Add the first item and it shows up on this page right away.',
    ],

    'footer' => [
        'powered_by' => 'Built on :name',
        'contact' => 'Get in touch',
    ],

    'contact' => [
        'phone' => 'Call us',
        'whatsapp' => 'WhatsApp',
        'instagram' => 'Instagram',
        'map' => 'Find us on the map',
        'address' => 'Address',
    ],

    'fields' => [
        'required' => 'Required field',
        'optional' => 'Optional',
    ],

    'a11y' => [
        'primary_nav' => 'Primary navigation',
        'club_home' => 'Home',
        'connection_status' => 'Connection status',
        'move_up' => 'Move up',
        'move_down' => 'Move down',
        'reorder_keyboard_hint' => 'Drag the handle, or use the move up and move down buttons.',
    ],

    /*
     | Meta descriptions. Written for a search result and a shared link, not for
     | the page itself — one sentence, no exclamation marks (DESIGN.md §9).
     */
    'seo' => [
        'home' => 'Ready-made training plans and an illustrated exercise library, on your phone and working offline inside the gym.',
        'programs' => 'Every published training plan: its level, its goal and how many days it runs.',
        'exercises' => 'The full exercise library, sorted by muscle group, equipment and difficulty.',
        'about' => 'Where the gym is, how to reach it and how to get in touch.',
        'muscle' => ':muscle exercises available at the gym, each with its equipment and difficulty.',
        'program_day' => ':day of :program. The exercises, sets, reps and rest times.',
        'exercise' => ':exercise — the muscle it works, the equipment it needs and how it is performed.',
    ],

];
