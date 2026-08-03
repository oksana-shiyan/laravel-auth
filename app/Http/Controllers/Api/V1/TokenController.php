<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\IssueTokenAction;
use App\Actions\Auth\LogoutUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Tokens\StoreTokenRequest;
use App\Http\Resources\TokenResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TokenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tokens = $request->user()->tokens()->latest()->get();

        return response()->json([
            'data' => TokenResource::collection($tokens),
        ]);
    }

    public function store(StoreTokenRequest $request, IssueTokenAction $action): JsonResponse
    {
        $user = $request->user();
        $abilities = $user->resolveTokenAbilities($request->validated('abilities'));

        $token = $action->execute(
            $user,
            $request->validated('name'),
            $abilities,
            rotateExisting: false,
        );

        return response()->json([
            'message' => 'Token created successfully.',
            'data' => [
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'access_token' => new TokenResource($token->accessToken),
            ],
        ], 201);
    }

    public function destroy(Request $request, int $token): JsonResponse
    {
        $accessToken = $request->user()->tokens()->whereKey($token)->first();

        if (! $accessToken) {
            throw new NotFoundHttpException('Token not found.');
        }

        $accessToken->delete();

        return response()->json([
            'message' => 'Token revoked successfully.',
        ]);
    }

    public function destroyAll(Request $request, LogoutUserAction $action): JsonResponse
    {
        $action->revokeAll($request->user());

        return response()->json([
            'message' => 'All tokens revoked successfully.',
        ]);
    }
}
