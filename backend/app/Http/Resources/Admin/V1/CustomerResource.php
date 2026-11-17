<?php

namespace App\Http\Resources\Admin\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Customer */
class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'is_guest' => $this->is_guest,
            'is_blocked' => $this->is_blocked,
            'internal_notes' => $this->internal_notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
