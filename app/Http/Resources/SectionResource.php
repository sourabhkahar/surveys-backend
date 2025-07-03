<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionResource extends JsonResource
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
            'paper_id'=> $this->paper_id,
            'title'=> $this->section_name,
            'total_marks'=> $this->total_marks,
            'caption'=> $this->caption,
            'section_type'=> $this->section_type,
            'questions' => SurveyQuestionResource::collection($this->questions), 
            'created_at'=> $this->created_at,
            'updated_at'=> $this->updated_at,
        ];  
    }
}
