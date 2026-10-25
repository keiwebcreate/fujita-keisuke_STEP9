@props(['path', 'name', 'compact' => false])
@php
    $valid_path = is_string($path)
        && preg_match('/\Aproducts\/[A-Za-z0-9_\/-]+\.(?:jpe?g|png|webp)\z/i', $path) === 1;
    $has_image = $valid_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path);
@endphp
<div {{ $attributes->class(['product-image', 'product-image--compact' => $compact]) }}>
    @if ($has_image)
        <img class="product-image__content" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($path) }}" alt="{{ $name }}の商品画像" loading="lazy">
    @else
        <span class="product-image__placeholder" role="img" aria-label="{{ $name }}：画像なし">画像なし</span>
    @endif
</div>
