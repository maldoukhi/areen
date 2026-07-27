<?php

declare(strict_types=1);

return [

    'install' => [
        'title' => 'Install Areen on your phone',
        'body' => 'Open your plan straight from the home screen, and use it in the gym with no connection.',
        'action' => 'Install the app',
        'dismiss' => 'Not now',
        'ios_title' => 'Add Areen to your home screen',
        'ios_body' => 'In the Safari toolbar tap Share, then choose Add to Home Screen.',
        'ios_step_share' => 'Tap Share in the Safari toolbar at the bottom.',
        'ios_step_add' => 'Scroll and choose Add to Home Screen.',
    ],

    'update' => [
        'dismiss' => 'Dismiss',
        'body' => 'An update for Areen is ready.',
        'action' => 'Reload',
        'applying' => 'Updating',
    ],

    'offline' => [
        'badge' => 'Offline — saved here and sent later',
        'title' => 'You are out of range',
        'body' => 'Pages you opened before are still here. Head back to your plan and finish your session.',
        'unsynced' => 'Waiting to sync',
        'synced' => 'Synced',
    ],

    'wake_lock' => [
        'label' => 'Keep the screen awake',
    ],

    'rest' => [
        'label' => 'Rest timer',
        'start' => 'Start the rest',
        'pause' => 'Pause the timer',
        'resume' => 'Resume the rest',
        'reset' => 'Reset the timer',
        'extend' => 'Add :seconds seconds',
        'remaining' => 'Rest remaining',
        'done' => 'Rest is over',
    ],

];
