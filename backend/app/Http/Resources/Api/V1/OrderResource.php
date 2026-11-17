<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Order */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'delivery_type' => $this->delivery_type?->value,
            'fulfillment_date' => $this->fulfillment_date?->toDateString(),
            'fulfillment_time' => $this->fulfillment_time,
            'address_text' => $this->address_text,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'delivery_fee' => $this->delivery_fee,
            'recipient_is_self' => $this->recipient_is_self,
            'recipient_name' => $this->recipient_name,
            'recipient_phone' => $this->recipient_phone,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'total' => $this->total,
            'payment_plan' => $this->payment_plan,
            'paid_amount' => $this->paid_amount,
            'remaining_amount' => $this->remaining_amount,
            'payment_status' => $this->payment_status?->value,
            'payment_due_at' => $this->payment_due_at?->toIso8601String(),
            'customer_note' => $this->customer_note,
            'admin_note' => $this->admin_note,
            'rejection_reason' => $this->whenLoaded('rejectionReason', fn () => $this->rejectionReason?->name),
            'items' => $this->whenLoaded('items'),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'timeline' => $this->whenLoaded('statusLogs', function () {
                return $this->statusLogs->map(fn ($log) => [
                    'from' => $log->from_status,
                    'to' => $log->to_status,
                    'reason' => $log->reason,
                    'at' => $log->created_at?->toIso8601String(),
                ]);
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
