<?php

declare(strict_types=1);

return [

    'title' => 'Exercises',
    'singular' => 'Exercise',
    'library' => 'Exercise library',

    'muscle' => [
        'label' => 'Muscle',
        'any' => 'All muscles',
        'groups' => 'Muscle groups',
        'primary' => 'Primary muscle',
        'secondary' => 'Supporting muscles',
        'chest' => 'Chest',
        'back' => 'Back',
        'shoulders' => 'Shoulders',
        'biceps' => 'Biceps',
        'triceps' => 'Triceps',
        'forearms' => 'Forearms',
        'quads' => 'Quads',
        'hamstrings' => 'Hamstrings',
        'glutes' => 'Glutes',
        'calves' => 'Calves',
        'abs' => 'Abs',
        'full_body' => 'Full body',
        'cardio' => 'Cardio',
        'exercises_count' => ':count exercises',
    ],

    'equipment' => [
        'label' => 'Equipment',
        'bodyweight' => 'Bodyweight',
        'barbell' => 'Barbell',
        'dumbbell' => 'Dumbbell',
        'machine' => 'Machine',
        'cable' => 'Cable',
        'kettlebell' => 'Kettlebell',
        'band' => 'Resistance band',
        'bench' => 'Bench',
        'smith' => 'Smith machine',
        'none' => 'No equipment',
        'any' => 'Any equipment',
    ],

    'difficulty' => [
        'label' => 'Difficulty',
        'beginner' => 'Beginner',
        'intermediate' => 'Intermediate',
        'advanced' => 'Advanced',
        'any' => 'Any difficulty',
    ],

    'prescription' => [
        'sets' => 'Sets',
        'reps' => 'Reps',
        'sets_reps' => ':sets × :reps',
        'sets_value' => ':count sets',
        'reps_value' => ':count reps',
        'rest' => 'Rest',
        'rest_value' => ':seconds sec',
        'tempo' => 'Tempo',
        'tempo_hint' => 'Four numbers in seconds: down · pause · up · pause.',
        'weight' => 'Weight',
        'weight_note' => 'Weight note',
        'weight_note_hint' => 'Write what the trainee will understand, such as "70% of your max" or "empty bar".',
        'superset' => 'Superset',
        'superset_group' => 'Superset group',
        'superset_hint' => 'Run the linked exercises back to back, then rest once.',
        'warmup' => 'Warm-up',
        'cooldown' => 'Cool-down',
        'to_failure' => 'To failure',
        'each_side' => 'Each side',
    ],

    'coach_notes' => [
        'label' => 'Coach notes',
        'placeholder' => 'A short note on form, load, or what to avoid.',
    ],

    'media' => [
        'video' => 'Exercise video',
        'watch' => 'Watch the form',
        'play' => 'Play the video',
        'image' => 'Exercise image',
        'none_title' => 'Add a video for this exercise',
        'none_body' => 'Paste a YouTube link and it shows up here with a tappable still.',
        'invalid_url' => 'That YouTube link is not valid. Paste the full link.',
    ],

    'log' => [
        'title' => 'Log the set',
        'set_number' => 'Set :number',
        'reps_done' => 'Reps done',
        'weight_used' => 'Weight used',
        'save_set' => 'Log the set',
        'undo_set' => 'Undo the set',
        'completed' => 'Exercise done',
        'rest_timer' => 'Rest timer',
        'start_rest' => 'Start the rest',
        'skip_rest' => 'Skip the rest',
        'rest_done' => 'Rest is over. Start the next set.',
        'saved' => 'Set logged.',
        'saved_offline' => 'Saved on your phone, and sent once you are back online.',
    ],

    'search' => [
        'placeholder' => 'Search by exercise or muscle',
        'no_results_title' => 'Try another word',
        'no_results_body' => 'No exercise goes by that name. Search by muscle or by equipment.',
    ],

    'filters' => [
        'none_title' => 'Widen your search',
        'none_for' => 'No exercise uses :filter “:value”. Clear that filter or pick another.',
        'none_for_combination' => 'No exercise matches everything you picked. Drop one filter and try again.',
    ],
    'empty' => [
        'title' => 'Start with your first exercise',
        'body' => 'Add an exercise to the library and it is ready for every program you build.',
        'action' => 'Add an exercise',
    ],

];
