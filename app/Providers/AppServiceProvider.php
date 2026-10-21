<?php

namespace App\Providers;

use App\Application\Attachments\AttachmentStorage;
use App\Application\Attachments\TextExtractor;
use App\Application\Notifications\AccountMailer;
use App\Application\Notifications\Notifier;
use App\Application\Tokens\AccessTokenGateway;
use App\Domain\Attachments\AttachmentRepository;
use App\Domain\Projects\ProjectRepository;
use App\Domain\Tasks\TaskRepository;
use App\Domain\Users\UserRepository;
use App\Infrastructure\Auth\PassportTokenGateway;
use App\Infrastructure\Files\DocumentTextExtractor;
use App\Infrastructure\Files\LaravelAttachmentStorage;
use App\Infrastructure\Mail\LaravelNotifier;
use App\Infrastructure\Persistence\EloquentAttachmentRepository;
use App\Infrastructure\Persistence\EloquentProjectRepository;
use App\Infrastructure\Persistence\EloquentTaskRepository;
use App\Infrastructure\Persistence\EloquentUserRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public array $bindings = [
        ProjectRepository::class => EloquentProjectRepository::class,
        TaskRepository::class => EloquentTaskRepository::class,
        UserRepository::class => EloquentUserRepository::class,
        AccessTokenGateway::class => PassportTokenGateway::class,
        AttachmentRepository::class => EloquentAttachmentRepository::class,
        TextExtractor::class => DocumentTextExtractor::class,
        Notifier::class => LaravelNotifier::class,
        AccountMailer::class => LaravelNotifier::class,
    ];

    public function register(): void
    {
        $this->app->bind(AttachmentStorage::class, fn () => new LaravelAttachmentStorage(config('relay.attachments.disk')));
    }

    public function boot(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        Passport::authorizationView(fn (array $parameters) => view('mcp.authorize', $parameters));
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(90));
        Passport::personalAccessTokensExpireIn(now()->addYear());

        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(8)->by($request->ip()));
        RateLimiter::for('mcp', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('password-reset', fn (Request $request) => [Limit::perMinute(3)->by($request->ip()), Limit::perHour(10)->by(mb_strtolower((string) $request->input('email')))]);
        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(30)->by($request->user()?->id ?: $request->ip()));
    }
}
