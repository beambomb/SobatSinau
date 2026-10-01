<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Kelas yang Saya Ajar') }}
            </h2>
            <a href="{{ route('classrooms.create') }}" class="px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition">
                + Buat Kelas Baru
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @forelse($classrooms as $classroom)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 hover:shadow-md transition">
                        <div class="p-6 bg-indigo-600 text-white">
                            <h3 class="text-xl font-bold truncate">{{ $classroom->title }}</h3>
                            <p class="text-indigo-200 text-sm mt-1">{{ $classroom->subject ?? 'Umum' }}</p>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                                <span>Kode Kelas:</span>
                                <span class="font-mono bg-gray-100 px-2 py-1 rounded text-indigo-700 font-bold tracking-wider">{{ $classroom->code }}</span>
                            </div>
                            <div class="flex justify-between items-center pt-4 border-t border-gray-100">
                                <a href="{{ route('classrooms.show', $classroom) }}" class="text-indigo-600 font-semibold hover:underline">
                                    Buka Kelas &rarr;
                                </a>
                                <form action="{{ route('classrooms.destroy', $classroom) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus kelas ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-sm">Hapus</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 text-center py-12 bg-white rounded-lg shadow-sm border border-gray-200">
                        <p class="text-gray-500 mb-4">Belum ada kelas yang kamu buat.</p>
                        <a href="{{ route('classrooms.create') }}" class="text-indigo-600 font-semibold hover:underline">Mulai buat kelas pertamamu</a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
