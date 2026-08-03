<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\RegisterUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request, RegisterUserAction $action): JsonResponse
    {
        $result = $action->execute($request->validated());

        return response()->json([
            'message' => 'Registered successfully.',
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token']->plainTextToken,
                'token_type' => 'Bearer',
                'abilities' => $result['token']->accessToken->abilities,
                'expires_at' => $result['token']->accessToken->expires_at,
            ],
        ], 201);
    }
}
