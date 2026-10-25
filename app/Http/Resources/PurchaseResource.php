<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    /** @return array{id: int, product_id: int, quantity: int, remaining_stock: int} */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'product_id' => $this->resource->product_id,
            'quantity' => $this->resource->quantity,
            'remaining_stock' => $this->resource->product->stock,
        ];
    }
}
