@extends('layouts.app')

@section('title', 'Input PO')

@section('content')
<div class="container mx-auto py-6 px-4">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold mb-4">Input PO</h2>

        <form action="{{ route('po.store') }}" method="POST">
            @csrf

            <div class="mb-4">
                <label for="kode_po" class="block text-sm font-medium text-gray-700">Kode PO</label>
                <input type="text" id="kode_po" name="kode_po" required 
                    class="w-full px-4 py-2 border rounded-md focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label for="nama_shipto" class="block text-sm font-medium text-gray-700">Nama Kapal</label>
                <input type="text" id="nama_shipto" name="nama_shipto" required 
                    class="w-full px-4 py-2 border rounded-md focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label for="kode_shipto" class="block text-sm font-medium text-gray-700">Kode Kapal</label>
                <input type="text" id="kode_shipto" name="kode_shipto" required 
                    class="w-full px-4 py-2 border rounded-md focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label for="nama_product" class="block text-sm font-medium text-gray-700">Nama Product</label>
                <input type="text" id="nama_product" name="nama_product" required 
                    class="w-full px-4 py-2 border rounded-md focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label for="kode_product" class="block text-sm font-medium text-gray-700">Kode Product</label>
                <input type="text" id="kode_product" name="kode_product" required 
                    class="w-full px-4 py-2 border rounded-md focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label for="qty" class="block text-sm font-medium text-gray-700">Jumlah</label>
                <input type="number" id="qty" name="qty" required min="1" 
                    class="w-full px-4 py-2 border rounded-md focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label for="link" class="block text-sm font-medium text-gray-700">Link</label>
                <input type="text" id="link" name="link" required 
                    class="w-full px-4 py-2 border rounded-md focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('po.po_dashboard') }}" class="px-4 py-2 bg-gray-300 rounded-md hover:bg-gray-400">
                    Batal
                </a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Simpan PO
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
