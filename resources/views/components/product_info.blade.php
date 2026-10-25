@props(['product', 'showCompany' => false, 'showStock' => false])
<dl class="product-info">
    <div class="product-info__row"><dt class="product-info__label">商品名</dt><dd class="product-info__value">{{ $product->product_name }}</dd></div>
    <div class="product-info__row"><dt class="product-info__label">説明</dt><dd class="product-info__value product-info__value--multiline">{{ $product->description }}</dd></div>
    <div class="product-info__row"><dt class="product-info__label">画像</dt><dd class="product-info__value"><x-product_image :path="$product->img_path" :name="$product->product_name" /></dd></div>
    @isset($quantity)
        <div class="product-info__row"><dt class="product-info__label">数量</dt><dd class="product-info__value">{{ $quantity }}</dd></div>
    @endisset
    <div class="product-info__row"><dt class="product-info__label">金額</dt><dd class="product-info__value"><x-product_price :price="$product->price" /></dd></div>
    @if ($showStock)
        <div class="product-info__row"><dt class="product-info__label">残り</dt><dd class="product-info__value">{{ $product->stock }}</dd></div>
    @endif
    @if ($showCompany)
        <div class="product-info__row"><dt class="product-info__label">会社</dt><dd class="product-info__value">{{ $product->company->company_name }}</dd></div>
    @endif
</dl>
