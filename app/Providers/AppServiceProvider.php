<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\BodyMetric;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\Program;
use App\Models\ProgramDay;
use App\Models\ProgramExercise;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkoutLog;
use App\Policies\BodyMetricPolicy;
use App\Policies\ExercisePolicy;
use App\Policies\MuscleGroupPolicy;
use App\Policies\ProgramDayPolicy;
use App\Policies\ProgramExercisePolicy;
use App\Policies\ProgramPolicy;
use App\Policies\SettingPolicy;
use App\Policies\UserPolicy;
use App\Policies\WorkoutLogPolicy;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Registered by hand rather than left to discovery so the whole permission
     * map can be read in one place.
     *
     * @var array<class-string, class-string>
     */
    private const POLICIES = [
        Program::class => ProgramPolicy::class,
        ProgramDay::class => ProgramDayPolicy::class,
        ProgramExercise::class => ProgramExercisePolicy::class,
        Exercise::class => ExercisePolicy::class,
        MuscleGroup::class => MuscleGroupPolicy::class,
        User::class => UserPolicy::class,
        Setting::class => SettingPolicy::class,
        WorkoutLog::class => WorkoutLogPolicy::class,
        BodyMetric::class => BodyMetricPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach (self::POLICIES as $model => $policy) {
            Gate::policy($model, $policy);
        }

        /*
         * The door to the panel itself. Trainees never hold it, which is what
         * turns `/admin` into a 403 for them rather than a redirect.
         */
        Gate::define(
            'access-admin',
            static fn (User $user): bool => $user->is_active && ($user->isAdmin() || $user->isCoach()),
        );

        $this->pointAuthRedirectsAtTheOneDoor();
        $this->registerTheTraineeSurface();
        $this->registerThePrintSurface();
        $this->registerTheDiscoverySurface();
    }

    /**
     * `robots.txt`, the sitemap and the Open Graph card, kept in `routes/seo.php`.
     *
     * Registered here rather than in `bootstrap/app.php` for the same reason the
     * trainee and print surfaces are: this file is already where the route map
     * is assembled. The `web` group matters — `SetLocale` runs there, and the
     * sitemap and the share card are both rendered in the reader's language.
     */
    private function registerTheDiscoverySurface(): void
    {
        Route::middleware('web')->group(base_path('routes/seo.php'));
    }

    /**
     * The trainee's own screens and the endpoint their offline queue drains
     * into, kept in `routes/trainee.php` so the member surface can be reviewed
     * apart from the public one and the panel.
     *
     * Registered here rather than in `bootstrap/app.php` for the same reason the
     * auth redirects are: this file already holds the permission map, and the
     * trainee routes are guarded entirely by policies rather than by a role gate
     * at the door. The `web` group is what carries the session the sync endpoint
     * authenticates with — without it the queue would have nobody to belong to.
     */
    private function registerTheTraineeSurface(): void
    {
        Route::middleware('web')->group(base_path('routes/trainee.php'));
    }

    /**
     * The print sheet and the PDF download, kept in `routes/print.php` so P6
     * can be reviewed on its own rather than mixed into `routes/web.php`.
     *
     * Registered here for the same reason the trainee surface is: this file
     * already holds the permission map, and the `web` group is what carries
     * the session `EnsureProgramIsViewable` reads to tell an unlocked private
     * program apart from one nobody has opened the access code for yet.
     */
    private function registerThePrintSurface(): void
    {
        Route::middleware('web')->group(base_path('routes/print.php'));
    }

    /**
     * Registration is invite-only, so the platform has exactly one sign-in
     * screen and it lives with the panel. Wiring it here rather than in
     * `bootstrap/app.php` keeps the auth decision beside the permission map,
     * and means any future guarded route — a trainee dashboard included —
     * lands on the same door without further configuration.
     */
    private function pointAuthRedirectsAtTheOneDoor(): void
    {
        $toLogin = static fn (): string => route('admin.login');

        Authenticate::redirectUsing($toLogin);
        AuthenticationException::redirectUsing($toLogin);

        // A signed-in trainee has no business on the panel, so they are sent to
        // the public site rather than bounced into a 403.
        RedirectIfAuthenticated::redirectUsing(
            static fn (Request $request): string => $request->user()?->isTrainee() === false
                ? route('admin.dashboard')
                : url('/'),
        );
    }
}
