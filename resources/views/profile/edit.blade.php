<x-app-layout>
    <x-slot:title>Account Settings</x-slot:title>

    <x-slot:header>
        <div class="mx-auto flex max-w-5xl items-center gap-3">
            <a href="{{ route('dashboard') }}" class="btn-ghost !px-2" aria-label="Back">
                <x-icon name="arrow-left" />
            </a>
            <h1 class="text-lg font-bold tracking-tight text-ink">Account Settings</h1>
        </div>
    </x-slot:header>

    <x-card padding="p-6 sm:p-8">
        @include('profile.partials.update-profile-information-form')
    </x-card>

    <x-card padding="p-6 sm:p-8">
        @include('profile.partials.update-password-form')
    </x-card>

    <x-card padding="p-6 sm:p-8" class="!border-danger-500/30">
        @include('profile.partials.delete-user-form')
    </x-card>
</x-app-layout>
