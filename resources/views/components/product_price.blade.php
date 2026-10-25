@props(['price'])
<span {{ $attributes->merge(['class' => 'product-price']) }}>¥{{ number_format($price) }}</span>
