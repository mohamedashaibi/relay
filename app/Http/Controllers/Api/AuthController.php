<?php

namespace App\Http\Controllers\Api;

use App\Application\Users\CompletePasswordResetUseCase;
use App\Application\Users\RegisterUserUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Session authentication for the SPA. AI clients authenticate with OAuth / personal tokens instead. */
class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegisterUserUseCase $register): JsonResponse
    {
        $data = $request->validated();
        $user = $register->execute($data['name'], $data['email'], $data['password']);

        Auth::loginUsingId($user->id);
        $request->session()->regenerate();

        return response()->json(['user' => $user->toArray(), 'redirect' => $this->intended($request)], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $credentials = ['email' => mb_strtolower(trim($data['email'])), 'password' => $data['password']];

        if (! Auth::attempt($credentials, (bool) ($data['remember'] ?? false))) {
            throw ValidationException::withMessages(['email' => 'Those credentials don\'t match our records.']);
        }

        $request->session()->regenerate();

        return response()->json(['user' => $this->me($request), 'redirect' => $this->intended($request)]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['ok' => true]);
    }

    /** Same answer whether or not the account exists, so emails can't be probed. */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink(['email' => mb_strtolower(trim($request->validated('email')))]);

        return response()->json(['message' => 'If an account uses that email, a reset link is on its way. Check your inbox (and spam folder).']);
    }

    public function resetPassword(ResetPasswordRequest $request, CompletePasswordResetUseCase $complete): JsonResponse
    {
        $data = $request->validated();

        $status = Password::reset(
            ['email' => mb_strtolower(trim($data['email'])), 'password' => $data['password'], 'token' => $data['token']],
            function (User $user, string $password) use ($complete) {
                $complete->execute($user->id, $password);

                // Sign out everywhere: invalidate "remember me" and any open sessions.
                $user->forceFill(['remember_token' => Str::random(60)])->save();
                if (config('session.driver') === 'database') {
                    DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
                }
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'This reset link is invalid or has expired. Request a new one.']);
        }

        return response()->json(['message' => 'Your password has been reset. Sign in with your new password.']);
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->me($request)]);
    }

    private function me(Request $request): array
    {
        $user = $request->user();

        return ['id' => $user->id, 'name' => $user->name, 'email' => $user->email];
    }

    /** Where to send the browser after login — e.g. back to an OAuth consent screen. Same-origin only. */
    private function intended(Request $request): ?string
    {
        $url = $request->session()->pull('url.intended');

        return is_string($url) && Str::startsWith($url, url('/')) ? $url : null;
    }
}
