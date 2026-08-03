<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Laravel\Sanctum\PersonalAccessToken
 */
class TokenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'abilities' => $this->abilities,
            'last_used_at' => $this->last_used_at,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
            'user' => $this->when(
                $this->relationLoaded('tokenable') && $this->tokenable,
                fn () => [
                    'id' => $this->tokenable->id,
                    'name' => $this->tokenable->name,
                    'email' => $this->tokenable->email,
                ],
            ),
        ];
    }
}
