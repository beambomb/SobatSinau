<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                    {{ $classroom->title }}
                </h2>
                <p class="text-sm text-gray-500">{{ $classroom->subject ?? 'Umum' }}</p>
            </div>
            <div class="bg-indigo-50 border border-indigo-200 px-4 py-2 rounded-lg text-right">
                <span class="text-xs text-indigo-500 uppercase tracking-wider block font-semibold">Kode Gabung Siswa</span>
                <span class="font-mono text-xl font-bold text-indigo-700 tracking-widest">{{ $classroom->code }}</span>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                <h3 class="text-lg font-bold mb-2">Forum / Stream Kelas</h3>
                <p class="text-gray-600 text-sm">Ruang kelas berhasil dibuat! Nanti di sini guru dan murid bisa berbagi materi, posting pengumuman, dan membuat tugas.</p>
            </div>
        </div>
    </div>
</x-app-layout>
