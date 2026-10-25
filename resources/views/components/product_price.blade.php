{{-- 金額を3桁区切りにそろえる部品です。例：1000を¥1,000と表示します。 --}}
@props(['price'])
<span {{ $attributes->merge(['class' => 'product-price']) }}>¥{{ number_format($price) }}</span>
