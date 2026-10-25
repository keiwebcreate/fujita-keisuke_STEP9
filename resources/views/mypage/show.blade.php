<x-app-layout>
    <x-slot name="header"><h1 class="page-header__title">マイページ</h1></x-slot>
    <div class="page-content">
        <div class="page-content__container page-content__container--stacked">
            @if (session('status'))
                <p class="status-message status-message--success" role="status">{{ session('status') }}</p>
            @endif
            <section class="content-panel" aria-labelledby="userInfoTitle">
                <div class="content-panel__body">
                    <div class="mypage-section__header">
                        <h2 id="userInfoTitle" class="mypage-section__title">ユーザー情報</h2>
                    </div>
                    <div class="mypage-section__account-action"><x-action_link :href="route('account.edit')">アカウント編集</x-action_link></div>
                    <dl class="user-info">
                        <div class="user-info__row"><dt class="user-info__label">ユーザー名</dt><dd class="user-info__value">{{ $user->name }}</dd></div>
                        <div class="user-info__row"><dt class="user-info__label">Eメール</dt><dd class="user-info__value">{{ $user->email }}</dd></div>
                        <div class="user-info__row"><dt class="user-info__label">名前</dt><dd class="user-info__value">{{ $user->name_kanji }}</dd></div>
                        <div class="user-info__row"><dt class="user-info__label">カナ</dt><dd class="user-info__value">{{ $user->name_kana ?? '未設定' }}</dd></div>
                    </dl>
                </div>
            </section>
            <section class="content-panel" aria-labelledby="listedProductsTitle">
                <div class="content-panel__body">
                    <div class="mypage-section__header">
                        <h2 id="listedProductsTitle" class="mypage-section__title">出品商品一覧</h2>
                        <x-action_link :href="route('products.create')">新規登録</x-action_link>
                    </div>
                    @if ($user->products->isEmpty())
                        <p class="empty-message" role="status">出品した商品はありません。</p>
                    @else
                        <div class="product-table__scroll" role="region" aria-label="自分の出品商品一覧の表" tabindex="0">
                            <table class="product-table">
                                <caption class="product-table__caption">自分の出品商品</caption>
                                <thead><tr><th scope="col">商品番号</th><th scope="col">商品名</th><th scope="col">商品説明</th><th scope="col">料金（¥）</th><th scope="col">操作</th></tr></thead>
                                <tbody>
                                    @foreach ($user->products as $product)
                                        <tr>
                                            <td>{{ $product->id }}</td>
                                            <td class="product-table__name">{{ $product->product_name }}</td>
                                            <td class="product-table__description">{{ $product->description }}</td>
                                            <td class="product-table__price"><x-product_price :price="$product->price" /></td>
                                            <td><x-action_link :href="route('listed_products.show', $product->id)" variant="detail" :aria-label="$product->product_name.'の出品詳細'">詳細</x-action_link></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>
            <section class="content-panel" aria-labelledby="purchasedProductsTitle">
                <div class="content-panel__body">
                    <h2 id="purchasedProductsTitle" class="mypage-section__title">購入商品一覧</h2>
                    @if ($user->sales->isEmpty())
                        <p class="empty-message" role="status">購入した商品はありません。</p>
                    @else
                        <div class="product-table__scroll" role="region" aria-label="自分の購入商品一覧の表" tabindex="0">
                            <table class="product-table">
                                <caption class="product-table__caption">自分の購入商品</caption>
                                <thead><tr><th scope="col">商品名</th><th scope="col">商品説明</th><th scope="col">料金（¥）</th><th scope="col">個数</th></tr></thead>
                                <tbody>
                                    @foreach ($user->sales as $sale)
                                        <tr>
                                            <td class="product-table__name">{{ $sale->product->product_name }}</td>
                                            <td class="product-table__description">{{ $sale->product->description }}</td>
                                            <td class="product-table__price"><x-product_price :price="$sale->product->price" /></td>
                                            <td>{{ $sale->quantity }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
