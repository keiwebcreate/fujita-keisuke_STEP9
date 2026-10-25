<x-app-layout>
    <x-slot name="header">
        <h2 class="page-header__title">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="page-content">
        <div class="page-content__container page-content__container--stacked">
            <div class="profile-panel">
                <div class="profile-panel__content">
                    @include('profile.partials.update_profile_information_form')
                </div>
            </div>

            <div class="profile-panel">
                <div class="profile-panel__content">
                    @include('profile.partials.update_password_form')
                </div>
            </div>

            <div class="profile-panel">
                <div class="profile-panel__content">
                    @include('profile.partials.delete_user_form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
