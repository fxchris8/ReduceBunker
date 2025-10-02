@extends('layouts.app')

@section('title', 'Monitoring PO')

@section('content')
<div class="container mx-auto py-6 px-4">
    <div class="bg-white rounded-lg shadow-md">
        <div class="bg-gray-50 px-6 py-4 border-b">
            <h1 class="text-xl font-bold">Monitoring PO</h1>
        </div>
        <div class="p-6">
            <div class="border rounded-md">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode PO</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Produk</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Kapal</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Sisa</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Pengisian</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($po as $item)
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $item->No_Po }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $item->nama_Products }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $item->nama_Shiptos }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 text-right">{{ $item->Quantity }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 text-right">{{ $item->Qty_Sisa  }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 text-right">
                                <input type="number" class="qty-input border rounded-md p-1 text-right w-20" 
                                    data-id="{{ $item->id_Po }}" placeholder="0">
                            </td>
                            <td class="px-6 py-4 text-sm text-right">
                                <button class="update-btn px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700" 
                                        data-id="{{ $item->id_Po }}">
                                    Update
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- jQuery untuk AJAX -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $('.update-btn').on('click', function() {
        var poId = $(this).data('id'); 
        var pemakaian = $(this).closest('tr').find('.qty-input').val();

        console.log('PO ID:', poId); 
        console.log('Pemakaian:', pemakaian); 

        if (!pemakaian || pemakaian <= 0) {
            alert('Masukkan jumlah pemakaian yang valid');
            return;
        }

        if (!poId) {
            alert('ID PO tidak valid');
            return;
        }

        $.ajax({
            url: "{{ route('po.monitoring.update') }}",
            method: "POST",
            data: { id_Po: poId, qty_digunakan: pemakaian },
            success: function(response) {
                alert(response.message);
                location.reload(); 
            },
            error: function(xhr) {
                console.error(xhr); 
                alert(xhr.responseJSON?.error || 'Terjadi kesalahan saat menyimpan data.');
            }
        });
    });
});
</script>
@endsection
    