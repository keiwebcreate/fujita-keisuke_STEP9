<x-app-layout>
    <x-slot name="header">
        <h2 class="page-header__title">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="page-content">
        <div class="page-content__container">
            <div class="content-panel">
                <div class="content-panel__body">
                    {{ __("You're logged in!") }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
