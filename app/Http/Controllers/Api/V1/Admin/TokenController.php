<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\IndexTokenRequest;
use App\Http\Resources\TokenResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TokenController extends Controller
{
    public function index(IndexTokenRequest $request): JsonResponse
    {
        $tokens = PersonalAccessToken::query()
            ->with('tokenable')
            ->when($request->filled('user_id'), function ($query) use ($request) {
                $query->where('tokenable_type', User::class)
                    ->where('tokenable_id', $request->integer('user_id'));
            })
            ->when($request->filled('name'), function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->string('name').'%');
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search').'%';

                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', $search)
                        ->orWhereHasMorph('tokenable', [User::class], function ($owner) use ($search) {
                            $owner->where('name', 'like', $search)
                                ->orWhere('email', 'like', $search);
                        });
                });
            })
            ->when($request->has('expired'), function ($query) use ($request) {
                if ($request->boolean('expired')) {
                    $query->whereNotNull('expires_at')
                        ->where('expires_at', '<', now());
                } else {
                    $query->where(function ($inner) {
                        $inner->whereNull('expires_at')
                            ->orWhere('expires_at', '>=', now());
                    });
                }
            })
            ->when($request->filled('ability'), function ($query) use ($request) {
                $query->where('abilities', 'like', '%"'.$request->string('ability').'"%');
            })
            ->orderBy($request->sortColumn(), $request->sortDirection())
            ->paginate($request->perPage())
            ->withQueryString();

        return TokenResource::collection($tokens)->response();
    }

    public function destroy(int $token): JsonResponse
    {
        $accessToken = PersonalAccessToken::query()->whereKey($token)->first();

        if (! $accessToken) {
            throw new NotFoundHttpException('Token not found.');
        }

        $accessToken->delete();

        return response()->json([
            'message' => 'Token revoked successfully.',
        ]);
    }
}
