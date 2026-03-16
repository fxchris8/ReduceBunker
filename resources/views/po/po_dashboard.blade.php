@extends('layouts.app')

@section('title', 'Cari PO Number')

@section('content')
<div class="container mx-auto py-6 px-4">
    <div class="bg-white rounded-lg shadow-md">
        <div class="bg-gray-50 px-6 py-4 border-b">
            <h1 class="text-xl font-bold">Cari PO Number</h1>
        </div>
        <div class="p-6">
            <form id="searchForm" class="flex flex-col md:flex-row gap-4 mb-6">
                <div class="flex-1">
                    <div class="relative">
                        <span class="absolute left-3 top-3 text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" class="lucide lucide-search"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </span>
                        <input 
                            type="text" 
                            name="PO" 
                            id="poSearch"
                            placeholder="Masukkan Nomer PO..." 
                            class="w-full pl-10 pr-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            value="{{ request('PO') }}"
                        >
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="button" id="searchBtn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Cari PO
                    </button>
            </form>
                    <a href="{{ route('po.create') }}" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Input PO
                    </a>
                    <a href="{{ route('po.monitoring') }}" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Monitoring
                    </a>
                    <a onclick="openModal()" class="px-4 py-2 bg-blue-600 text-white rounded-md">Upload File</a>
                </div>

            <!-- Modal Upload -->
            <div id="uploadModal" class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden">
                <div class="bg-white p-6 rounded-lg shadow-lg w-96">
                    <h2 class="text-xl font-bold mb-4">Upload File</h2>
                    <form action="{{ route('upload.file') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="file" accept=".csv, .xlsx" required class="mb-4">
                        <div class="flex justify-end">
                            <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-300 rounded-md mr-2">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">Upload</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="border rounded-md">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Kapal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Produk</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah Sisa</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Link</th>
                        </tr>
                    </thead>
                    <tbody id="purchaseOrdersTableBody" class="bg-white divide-y divide-gray-200">
                        @if ($purchaseOrders->isEmpty())
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" colspan="6">Tidak ada data</td>
                            </tr>
                        @else
                            @foreach ($purchaseOrders as $po)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $po->nama_Shiptos }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $po->nama_Products }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $po->Quantity }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ $po->Qty_Sisa }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                        <span class="{{ $po->Status == 'OPEN' ? 'status-open' : 'status-close' }}">
                                            {{ $po->Status }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Script untuk menampilkan modal -->
<script>
function openModal() { document.getElementById('uploadModal').classList.remove('hidden'); }
function closeModal() { document.getElementById('uploadModal').classList.add('hidden'); }
</script>

<!-- Tambahkan jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Handle search functionality dengan AJAX
    $('#searchBtn').on('click', function () {
        var poCode = $('#poSearch').val(); // Ambil nilai input field

        $.ajax({
            url: "{{ route('dashboard.search') }}", // Rute untuk fungsi pencarian
            method: 'GET',
            data: {
                PO: poCode
            },
            success: function (data) {
                // Kosongkan tabel sebelum menampilkan data baru
                $('#purchaseOrdersTableBody').empty();

                // Periksa apakah ada data
                if (data.length === 0) {
                    $('#purchaseOrdersTableBody').append('<tr><td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Tidak ada data</td></tr>');
                } else {
                    // Loop melalui setiap purchase order dan tambahkan ke tabel    
                    $.each(data, function (index, po) {
                        $('#purchaseOrdersTableBody').append(`
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${po.nama_Shiptos}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${po.nama_Products}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${po.Quantity}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">${po.Qty_Sisa}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                    <span class="${po.Status == 'OPEN' ? 'status-open' : 'status-close'}">
                                        ${po.Status}
                                    </span>
                                </td>
                            </tr>
                        `);
                    });
                }
            },
            error: function () {
                alert('Terjadi kesalahan saat mencari data.');
            }
        });
    });
</script>


<style>
    .status-open {
        color: green;
        font-weight: bold;
    }

    .status-close {
        color: red;
        font-weight: bold;
    }
</style>

@endsection
