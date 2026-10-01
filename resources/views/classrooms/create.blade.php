<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Buat Kelas Baru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg border border-gray-200">
                <form action="{{ route('classrooms.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-4">
                        <x-input-label for="title" :value="__('Nama Kelas (Wajib)')" />
                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" placeholder="Contoh: Matematika Wajib XII" required autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('title')" />
                    </div>

                    <div class="mb-6">
                        <x-input-label for="subject" :value="__('Mata Pelajaran / Bagian (Opsional)')" />
                        <x-text-input id="subject" name="subject" type="text" class="mt-1 block w-full" placeholder="Contoh: Semester Ganjil 2026" />
                        <x-input-error class="mt-2" :messages="$errors->get('subject')" />
                    </div>

                    <div class="flex items-center justify-end gap-4">
                        <a href="{{ route('classrooms.index') }}" class="text-gray-600 hover:underline text-sm">Batal</a>
                        <x-primary-button>{{ __('Buat Kelas') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
