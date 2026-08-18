<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SerpResultResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'position'   => $this->position,
            'url'        => $this->url,
            'title'      => $this->title,
            'checked_at' => $this->checked_at,
        ];
    }
}