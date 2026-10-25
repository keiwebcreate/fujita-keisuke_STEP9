@if ($products->isEmpty())
    <p class="empty-message" role="status">表示できる商品はありません。</p>
@else
    <div class="product-table__scroll" role="region" aria-label="商品一覧の表" tabindex="0">
        <table class="product-table">
            <caption class="product-table__caption">出品商品の一覧</caption>
            <thead><tr>
                <th scope="col">商品番号</th><th scope="col">商品名</th><th scope="col">商品説明</th><th scope="col">画像</th><th scope="col">料金（¥）</th><th scope="col">操作</th>
            </tr></thead>
            <tbody>
                @foreach ($products as $product)
                    <tr>
                        <td>{{ $product->id }}</td>
                        <td class="product-table__name">{{ $product->product_name }}</td>
                        <td class="product-table__description">{{ $product->description }}</td>
                        <td><x-product_image :path="$product->img_path" :name="$product->product_name" compact /></td>
                        <td class="product-table__price"><x-product_price :price="$product->price" /></td>
                        <td><x-action_link :href="route('products.show', $product->id)" variant="detail" :aria-label="$product->product_name.'の詳細'">詳細</x-action_link></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
