<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Documents
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white p-6 shadow sm:rounded-lg mb-6">
                <h3 class="font-medium mb-4">Upload a document</h3>

                <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="file" class="mb-4">
                    @error('file')
                        <p class="text-red-600 text-sm">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2 rounded border border-indigo-800">
                        Upload
                    </button>
                </form>
            </div>

            <div class="bg-white p-6 shadow sm:rounded-lg">
                <h3 class="font-medium mb-4">Your documents</h3>

                @forelse ($documents as $document)
                    <div class="flex justify-between items-center border-b py-2">
                        <div>
                            <p>{{ $document->title }}</p>
                            <p class="text-sm text-gray-500">Status: {{ $document->status }}</p>
                        </div>
                        <form method="POST" action="{{ route('documents.destroy', $document) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 text-sm">Delete</button>
                        </form>
                    </div>
                @empty
                    <p class="text-gray-500">No documents uploaded yet.</p>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>