@extends('layouts.app')

@section('title', 'Monitoring PO')

@section('content')
<div class="container mx-auto py-6 px-4">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold mb-4">Monitoring PO</h2>

        <!-- Tombol untuk membuka modal -->
        <button @click="open = true" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            Upload PO
        </button>

        <!-- Modal Pop-up -->
        <div x-data="{ open: false }">
            <div x-show="open" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex justify-center items-center">
                <div class="bg-white p-6 rounded-lg shadow-lg w-96">
                    <h2 class="text-lg font-bold mb-4">Upload File PO</h2>

                    @if(session('success'))
                        <div class="bg-green-500 text-white p-3 rounded-md mb-4">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="bg-red-500 text-white p-3 rounded-md mb-4">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('po.po_upload') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Pilih File</label>
                            <input type="file" name="file" required 
                                class="w-full px-4 py-2 border rounded-md focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="flex justify-end gap-2">
                            <button type="button" @click="open = false" 
                                class="px-4 py-2 bg-gray-300 rounded-md hover:bg-gray-400">
                                Batal
                            </button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Upload
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

<!-- Tambahkan Alpine.js -->
@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endsection