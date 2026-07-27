<?php

declare(strict_types=1);

return [

    /*
     | The three keys below are read by the framework itself. Keep their names.
     */
    'failed' => 'That email and password do not match. Check them and try again.',
    'password' => 'That password is not correct. Enter it again.',
    'throttle' => 'Too many sign-in attempts. Wait :seconds seconds and try again.',

    'login' => [
        'title' => 'Sign in to your account',
        'subtitle' => 'Sign in to follow your plan and log your sets.',
        'action' => 'Sign in',
        'remember' => 'Keep me signed in',
        'forgot' => 'Forgot your password',
        'no_account' => 'No account yet',
        'create_account' => 'Create an account',
    ],

    'register' => [
        'title' => 'Create your account',
        'subtitle' => 'Keep your progress and pick your plan up on any device.',
        'action' => 'Create the account',
        'have_account' => 'Already have an account',
        'sign_in' => 'Sign in',
    ],

    'logout' => [
        'action' => 'Sign out',
        'done' => 'You are signed out.',
    ],

    'fields' => [
        'name' => 'Name',
        'name_placeholder' => 'The name you want shown',
        'email' => 'Email address',
        'phone' => 'Mobile number',
        'password' => 'Password',
        'password_hint' => 'Eight characters or more.',
        'password_confirmation' => 'Repeat the password',
        'current_password' => 'Current password',
        'new_password' => 'New password',
        'show_password' => 'Show the password',
        'hide_password' => 'Hide the password',
    ],

    'reset' => [
        'request_title' => 'Recover your password',
        'request_body' => 'Enter your email and we send you a reset link.',
        'request_action' => 'Send the link',
        'title' => 'Choose a new password',
        'action' => 'Save the password',
        'sent' => 'We sent a reset link to your email. Open it within the hour.',
        'done' => 'Your new password is saved. Sign in with it now.',
    ],

    'verify' => [
        'title' => 'Confirm your email',
        'body' => 'We sent a confirmation link to your email. Open it to activate your account.',
        'resend' => 'Send the link again',
        'sent' => 'A new link is on its way to your email.',
    ],

    'confirm' => [
        'title' => 'Confirm it is you',
        'body' => 'Enter your password to carry on with this step.',
        'action' => 'Confirm',
    ],

    'errors' => [
        'inactive' => 'Your account is suspended. Talk to the club to switch it back on.',
        'unauthorized' => 'You do not have access to this page. Head back home.',
        'guest_only' => 'You are already signed in. Head back to your dashboard.',
    ],

    'account' => [
        'title' => 'My account',
        'profile' => 'My details',
        'preferences' => 'Preferences',
        'language' => 'Interface language',
        'change_password' => 'Change the password',
        'saved' => 'Your details are saved.',
    ],

];
