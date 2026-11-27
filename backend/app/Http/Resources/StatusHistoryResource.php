<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatusHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'previous_status' => $this->previous_status,
            'new_status' => $this->new_status,
            'changed_at' => $this->created_at,
            'actor' => $this->whenLoaded('actor', fn () => [
                'id' => $this->actor->id,
                'name' => $this->actor->name,
                'role' => $this->actor->role?->slug,
            ]),
        ];
    }
}
