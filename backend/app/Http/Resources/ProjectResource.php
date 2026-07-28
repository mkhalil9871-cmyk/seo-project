<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'domain'     => $this->domain,
            'industry'   => $this->industry,
            'country'    => $this->country,
            'language'   => $this->language,
            'status'     => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}