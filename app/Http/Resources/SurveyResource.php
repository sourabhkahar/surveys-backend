<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyResource extends JsonResource
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
            'slug'=> $this->slug,
            'image'=>$this->image,
            'status'=> $this->status !== 'draft',
            'description'=> $this->description,
            'created_at'=> $this->created_at,
            'updated_at'=> $this->updated_at,
            'expire_date'=> $this->expire_date,
            'questions'=> SurveyQuestionResource::collection($this->whenLoaded('questions'))
        ];
    }
}
