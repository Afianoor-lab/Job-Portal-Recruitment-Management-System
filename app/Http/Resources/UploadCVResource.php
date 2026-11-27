<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UploadCVResource extends JsonResource
{

    
    public function toArray(Request $request): array
    {
        
        return [
            'id'         => $this->id,
            'candidate_id'      => $this->candidate_id,
            'file_path'    => $this->file_path,
            'mime_type'   => $this->mime_type ,
            'size'  => $this->size,
            'path'       => url("/api/v1/candidate/cv/{$this->id}") ,
            'is_current'      => $this->is_current,
            'uploaded_at' => $this->uploaded_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
