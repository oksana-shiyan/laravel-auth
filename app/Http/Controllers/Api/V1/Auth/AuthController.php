<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\LoginUserAction;
use App\Actions\Auth\LogoutUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthController extends Controller
{
    public function login(LoginRequest $request, LoginUserAction $action): JsonResponse
    {
        $result = $action->execute($request->validated());

        return response()->json([
            'message' => 'Authenticated successfully.',
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token']->plainTextToken,
                'token_type' => 'Bearer',
                'abilities' => $result['token']->accessToken->abilities,
                'expires_at' => $result['token']->accessToken->expires_at,
            ],
        ]);
    }

    public function logout(Request $request, LogoutUserAction $action): JsonResponse
    {
        $action->execute($request->user());

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function notice(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.',
                'data' => [
                    'user' => new UserResource($request->user()->load(['roles', 'permissions'])),
                ],
            ]);
        }

        return response()->json([
            'message' => 'Email address is not verified.',
        ], 409);
    }

    public function send(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.',
            ]);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification link sent.',
        ]);
    }

    public function verify(Request $request, int $id, string $hash): JsonResponse
    {
        $user = User::query()->findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            throw new AccessDeniedHttpException('Invalid verification hash.');
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.',
                'data' => [
                    'user' => new UserResource($user->load(['roles', 'permissions'])),
                ],
            ]);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json([
            'message' => 'Email verified successfully.',
            'data' => [
                'user' => new UserResource($user->fresh(['roles', 'permissions'])),
            ],
        ]);
    }
}
