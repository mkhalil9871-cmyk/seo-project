<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class KeywordResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'keyword'     => $this->keyword,
            'cluster'     => $this->cluster,
            'intent'      => $this->intent,
            'serp_status' => $this->serp_status,
            'results'     => SerpResultResource::collection($this->whenLoaded('serpResults')),
            'created'     => $this->created_at,
        ];
    }
}