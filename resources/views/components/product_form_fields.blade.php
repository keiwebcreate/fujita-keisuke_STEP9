@props(['product' => null, 'imageRequired' => true])
<div class="form-field">
    <x-input_label for="productName" value="商品名（必須）" />
    <x-text_input id="productName" name="product_name" type="text" :value="old('product_name', $product?->product_name)" required maxlength="255" />
    <x-input_error :messages="$errors->get('product_name')" />
</div>
<div class="form-field">
    <x-input_label for="productPrice" value="価格（必須）" />
    <x-text_input id="productPrice" name="price" type="text" inputmode="numeric" :value="old('price', $product?->price)" required maxlength="10" />
    <x-input_error :messages="$errors->get('price')" />
</div>
<div class="form-field">
    <x-input_label for="productDescription" value="商品説明（必須）" />
    <textarea id="productDescription" name="description" class="form-field__input" rows="4" required maxlength="255">{{ old('description', $product?->description) }}</textarea>
    <x-input_error :messages="$errors->get('description')" />
</div>
<div class="form-field">
    <x-input_label for="productStock" value="在庫数（必須）" />
    <x-text_input id="productStock" name="stock" type="text" inputmode="numeric" :value="old('stock', $product?->stock)" required maxlength="10" />
    <x-input_error :messages="$errors->get('stock')" />
</div>
<div class="form-field">
    <x-input_label for="productImage" :value="$imageRequired ? '商品画像（必須）' : '商品画像'" />
    @if ($product)
        <x-product_image :path="$product->img_path" :name="$product->product_name" />
    @endif
    <input id="productImage" name="image" type="file" class="product-form__file" accept="image/jpeg,image/png,image/webp" @required($imageRequired) aria-describedby="productImageHelp">
    <p id="productImageHelp" class="product-form__help">JPEG・PNG・WebP、2MiB以下。入力エラー時は画像を再選択してください。</p>
    <x-input_error :messages="$errors->get('image')" />
</div>
