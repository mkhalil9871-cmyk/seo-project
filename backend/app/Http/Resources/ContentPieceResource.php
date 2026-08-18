<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContentPieceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'body'         => $this->body,
            'status'       => $this->status,
            'keyword_id'   => $this->keyword_id,
            'generated_at' => $this->generated_at,
            'created'      => $this->created_at,
        ];
    }
}