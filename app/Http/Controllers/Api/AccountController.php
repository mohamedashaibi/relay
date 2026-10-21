<?php

namespace App\Http\Controllers\Api;

use App\Application\Users\ChangePasswordUseCase;
use App\Application\Users\GetEmailPreferencesUseCase;
use App\Application\Users\UpdateEmailPreferencesUseCase;
use App\Application\Users\UpdateProfileUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateEmailPreferencesRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function show(Request $request, GetEmailPreferencesUseCase $preferences): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            'email_preferences' => $preferences->execute($user->id),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request, UpdateProfileUseCase $update): JsonResponse
    {
        return response()->json(['user' => $update->execute($request->user()->id, $request->validated('name'))->toArray()]);
    }

    public function changePassword(ChangePasswordRequest $request, ChangePasswordUseCase $change): JsonResponse
    {
        $data = $request->validated();
        $change->execute($request->user()->id, $data['current_password'], $data['password']);

        // Keep this browser signed in with the new credentials.
        $request->session()->regenerate();

        return response()->json(['ok' => true]);
    }

    public function updatePreferences(UpdateEmailPreferencesRequest $request, UpdateEmailPreferencesUseCase $update, GetEmailPreferencesUseCase $get): JsonResponse
    {
        $update->execute($request->user()->id, $request->validated('preferences'));

        return response()->json(['email_preferences' => $get->execute($request->user()->id)]);
    }
}
