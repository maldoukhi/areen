<?php

declare(strict_types=1);

return [

    'title' => 'Programs',
    'singular' => 'Program',
    'all' => 'All programs',
    'featured_title' => 'Picked for you',
    'featured' => 'Featured',
    'latest' => 'Latest programs',

    'visibility' => [
        'label' => 'Visibility',
        'public' => 'Public',
        'private' => 'Code only',
        'draft' => 'Draft',
        'published' => 'Published',
    ],

    'level' => [
        'label' => 'Level',
        'beginner' => 'Beginner',
        'intermediate' => 'Intermediate',
        'advanced' => 'Advanced',
        'any' => 'Any level',
    ],

    'goal' => [
        'label' => 'Goal',
        'strength' => 'Strength',
        'hypertrophy' => 'Muscle size',
        'fat-loss' => 'Fat loss',
        'endurance' => 'Endurance',
        'general-fitness' => 'General fitness',
        'any' => 'Any goal',
    ],

    'days' => [
        'label' => 'Days',
        'count' => ':count days a week',
        'number' => 'Day :number',
        'total' => '{0} No days|{1} 1 day|[2,*] :count days',
        'title' => 'Day :number · :title',
        'focus' => 'Focus',
        'notes' => 'Notes for the day',
        'exercises_count' => ':count exercises',
        'rest' => 'Rest day',
        'rest_title' => 'Today is a rest day',
        'rest_body' => 'Take the day off. Sleep well, drink enough water, and come back stronger tomorrow.',
        'none_title' => 'This plan is being put together',
        'none_body' => 'The days show up here as soon as they are published. Browse the other programs meanwhile.',
        'empty_title' => 'Build this day',
        'empty_body' => 'Add the first exercise and it shows up in the day right away.',
        'next' => 'Next day',
        'previous' => 'Previous day',
        'today' => 'Today',
        'log_set' => 'Log the set',
        'log_soon' => 'Logging sets starts with your account in the next update.',
    ],

    'access' => [
        'badge' => 'Private program',
        'code' => 'Access code',
        'code_hint' => 'Enter the code your coach gave you.',
        'code_placeholder' => 'Code',
        'open' => 'Open the program',
        'invalid' => 'That code is not right. Check it with your coach and try again.',
        'locked_title' => 'This program needs a code',
        'locked_body' => 'Enter the access code to open the full plan.',
    ],

    'actions' => [
        'view' => 'Open the program',
        'start' => 'Start the program',
        'continue' => 'Continue the program',
        'start_day' => 'Start the day',
        'browse' => 'Browse programs',
        'print' => 'Print the plan',
        'download_pdf' => 'Download the plan as PDF',
        'share' => 'Share the link',
        'copy_link' => 'Copy the link',
        'link_copied' => 'Link copied',
    ],

    'meta' => [
        'about' => 'About this program',
        'duration' => 'Duration',
        'weeks' => ':count weeks',
        'coach' => 'Coach',
        'published_on' => 'Published :date',
        'updated_on' => 'Updated :date',
        'started_on' => 'Started :date',
    ],

    'progress' => [
        'label' => 'Your progress',
        'completed_days' => ':done of :total days',
        'streak' => 'Days in a row',
        'last_session' => 'Last session',
    ],

    'print' => [
        'heading' => ':program plan',
        'printed_on' => 'Printed :date',
        'weight_used' => 'Weight used',
        'scan_hint' => 'Scan the code to open this plan on your phone.',
    ],

    'filters' => [
        'title' => 'Filter programs',
        'reset' => 'Reset',
        'no_results_title' => 'Widen your search a little',
        'no_results_body' => 'No program matches those filters. Try another level or another goal.',
    ],

    'empty' => [
        'title' => 'Start with your first program',
        'body' => 'Pick a program that fits your level and your goal, and your plan shows up here day by day.',
        'action' => 'Browse programs',
    ],

];
