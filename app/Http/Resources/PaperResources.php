<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaperResources extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
       return [
            'id'=> $this->id,
            'title'=> $this->title,
            'created_at'=> $this->created_at,
            'updated_at'=> $this->updated_at,
            'sections' => SectionResource::collection($this->sections) 
        ];
    }
}
