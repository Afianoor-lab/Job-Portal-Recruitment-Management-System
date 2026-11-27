<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'cover_letter' => $this->cover_letter,
            'created_at' => $this->created_at,
            'job' => $this->whenLoaded('jobPost', fn () => new JobResource($this->jobPost)),
            'candidate' => $this->whenLoaded('candidate', fn () => [
                'id' => $this->candidate->id,
                'name' => $this->candidate->name,
                'email' => $this->candidate->email,
                'profile' => $this->candidate->candidateProfile,
            ]),
            'cv' => $this->whenLoaded('cv', fn () => [
                'id' => $this->cv->id,
                'original_name' => $this->cv->original_name,
                'size' => $this->cv->size,
            ]),
            'history' => $this->whenLoaded(
                'histories',
                fn () => StatusHistoryResource::collection($this->histories),
            ),
        ];
    }
}
