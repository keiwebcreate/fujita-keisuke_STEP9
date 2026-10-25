{{-- 商品検索画面です。data-*属性はJavaScriptが検索欄や結果の場所を見つける目印です。 --}}
<x-app-layout>
    <x-slot name="header"><h1 class="page-header__title">商品一覧</h1></x-slot>
    <div class="page-content">
        <div class="page-content__container">
            <section class="content-panel" aria-label="商品一覧">
                <div class="content-panel__body">
                    @if (session('status'))
                        <p class="status-message status-message--success" role="status">{{ session('status') }}</p>
                    @endif
                    <form method="GET" action="{{ route('products.index') }}" class="product-search" data-product-search>
                        <div class="product-search__field product-search__field--name">
                            <x-input_label for="searchProductName" value="商品名" class="form-field__label--visually-hidden" />
                            <x-text_input id="searchProductName" name="product_name" type="text" :value="old('product_name', $filters['product_name'] ?? '')" placeholder="商品名を入力" maxlength="255" aria-describedby="searchProductNameErrors" />
                            <ul id="searchProductNameErrors" class="form-field__errors" data-search-errors="product_name">@foreach ($errors->get('product_name') as $message)<li>{{ $message }}</li>@endforeach</ul>
                        </div>
                        <div class="product-search__field">
                            <x-input_label for="searchMinPrice" value="最低価格" class="form-field__label--visually-hidden" />
                            <x-text_input id="searchMinPrice" name="min_price" type="text" inputmode="numeric" :value="old('min_price', $filters['min_price'] ?? '')" placeholder="最低価格" aria-describedby="searchMinPriceErrors" />
                            <ul id="searchMinPriceErrors" class="form-field__errors" data-search-errors="min_price">@foreach ($errors->get('min_price') as $message)<li>{{ $message }}</li>@endforeach</ul>
                        </div>
                        <span class="product-search__separator" aria-hidden="true">〜</span>
                        <div class="product-search__field">
                            <x-input_label for="searchMaxPrice" value="最高価格" class="form-field__label--visually-hidden" />
                            <x-text_input id="searchMaxPrice" name="max_price" type="text" inputmode="numeric" :value="old('max_price', $filters['max_price'] ?? '')" placeholder="最高価格" aria-describedby="searchMaxPriceErrors" />
                            <ul id="searchMaxPriceErrors" class="form-field__errors" data-search-errors="max_price">@foreach ($errors->get('max_price') as $message)<li>{{ $message }}</li>@endforeach</ul>
                        </div>
                        <x-primary_button class="product-search__button">検索</x-primary_button>
                    </form>
                    <p class="product-search__status" data-search-status role="status" aria-live="polite"></p>
                    <p class="product-search__error" data-search-error role="alert" hidden></p>
                    <div data-search-results aria-busy="false">
                        @include('products.product_list')
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
