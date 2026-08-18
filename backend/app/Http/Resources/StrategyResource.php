<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StrategyResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'           => $this->id,
            'status'       => $this->status,
            'content'      => $this->content,
            'generated_at' => $this->generated_at,
            'created'      => $this->created_at,
        ];
    }
}