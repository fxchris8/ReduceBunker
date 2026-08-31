@extends('layouts.app')

@section('title', 'Baseline Analysis')

@section('loader')
<div id="loader" class="fixed inset-0 bg-white bg-opacity-90 flex flex-col items-center justify-center z-50 hidden">
    <div class="w-16 h-16 border-4 border-gray-300 border-t-red-300 rounded-full animate-spin"></div>
    <p class="mt-4 text-red-600 font-semibold">Loading...</p>
</div>
@endsection

@section('content')

<div class="container mx-auto py-6 px-4">
    <div class="bg-white rounded-lg shadow-md">
        <div class="bg-gray-50 px-4 py-4 border-b flex justify-between items-center">
            <h1 class="text-xl font-bold">Baseline Analysis</h1>
        </div>

        <div class="border rounded-md p-6">
            @if(isset($error))
                <div class="bg-red-100 text-red-600 p-3 rounded mb-4">
                    {{ $error }}
                </div>
            @endif

            <div class="px-4 py-2 rounded-md text-lg">
                <form action="{{ route('po.baseline') }}" method="GET" id="filterForm" class="mb-6 space-y-4">
                    <div>
                        <label for="density" class="block text-sm font-medium text-gray-700 mb-2">Masukkan Density (g/L):</label>
                        <input type="number" step="any" name="density" id="density"
                            value="{{ $density }}"
                            class="block w-full max-w-sm px-4 py-2 border border-gray-300 rounded-lg shadow-sm bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-600 focus:border-red-600 transition duration-150 ease-in-out">
                    </div>

                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-2">
                        <div class="flex items-center gap-2 text-sm">
                            <div class="relative">
                                <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                    </svg>
                                </span>
                                <input type="text"
                                    name="search"
                                    id="searchInput"
                                    value="{{ $search ?? '' }}"
                                    placeholder="Search vessel code / name..."
                                    class="w-64 pl-9 pr-8 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-red-500">
                                @if(!empty($search))
                                    <a href="{{ route('po.baseline', ['density' => $density, 'per_page' => $perPage ?? 10]) }}"
                                    class="absolute inset-y-0 right-2 flex items-center text-gray-400 hover:text-gray-600 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-3 text-sm text-gray-500">
                            <div class="flex items-center gap-2">
                                <span>Show</span>
                                <select name="per_page"
                                        class="border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-red-500">
                                    @foreach(['10', '25', '50', 'all'] as $option)
                                        <option value="{{ $option }}" {{ (string)($perPage ?? '10') === $option ? 'selected' : '' }}>
                                            {{ $option === 'all' ? 'All' : $option }}
                                        </option>
                                    @endforeach
                                </select>
                                <span>entries</span>
                            </div>
                            <div class="w-px h-4 bg-gray-300"></div>
                            <span id="dataInfo">
                                @if(($isPaginated ?? false) && isset($vessels) && $vessels->count())
                                    Showing {{ $vessels->firstItem() }}–{{ $vessels->lastItem() }} of {{ $vessels->total() }} entries
                                @elseif(isset($vessels))
                                    Showing {{ $vessels->count() }} entries
                                @endif
                            </span>
                        </div>
                    </div>
                </form>

                <div id="tableWrapper">
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="py-2 px-4 border-b text-left text-sm font-semibold text-gray-700">Vessel Code</th>
                                    <th class="py-2 px-4 border-b text-left text-sm font-semibold text-gray-700">Vessel Name</th>
                                    <th class="py-2 px-4 border-b text-left text-sm font-semibold text-gray-700">Power ME</th>
                                    <th class="py-2 px-4 border-b text-left text-sm font-semibold text-gray-700">SFOC (L/kW/hr)</th>
                                    <th class="py-2 px-4 border-b text-left text-sm font-semibold text-gray-700">L/hr</th>
                                    <th class="py-2 px-4 border-b text-left text-sm font-semibold text-gray-700">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tableData as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-2 px-4 border-b text-sm text-gray-700">{{ $row['vessel_id'] }}</td>
                                    <td class="py-2 px-4 border-b text-sm text-gray-700">{{ $row['vessel_name'] }}</td>
                                    <td class="py-2 px-4 border-b text-sm text-gray-700">{{ $row['power_me'] }}</td>
                                    <td class="py-2 px-4 border-b text-sm text-gray-700">{{ $row['sfoc'] }}</td>
                                    <td class="py-2 px-4 border-b text-sm text-gray-700 font-semibold">{{ $row['l_hr'] }}</td>
                                    <td class="py-2 px-4 border-b text-sm text-gray-700">
                                        <a href="{{ route('po.baseline.detail', ['vessel' => $row['vessel_id'], 'density' => $density, 'power_kw' => $row['power_kw_num'], 'steam_time' => 1]) }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                                            View Detail
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                                @if(count($tableData) == 0)
                                <tr>
                                    <td colspan="6" class="py-4 text-center text-gray-500">No vessels data available.</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    @if(($isPaginated ?? false) && isset($vessels) && $vessels->hasPages())
                        <div id="paginationWrapper" class="mt-4">
                            {{ $vessels->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

<script>
document.addEventListener('DOMContentLoaded', function () {
    let debounceTimer;

    function getParams(page) {
        const params = new URLSearchParams();
        const searchInput = document.getElementById('searchInput');
        if (searchInput && searchInput.value) params.set('search', searchInput.value);
        
        const perPageSelect = document.querySelector('select[name="per_page"]');
        if (perPageSelect && perPageSelect.value) params.set('per_page', perPageSelect.value);
        
        const densityInput = document.getElementById('density');
        if (densityInput && densityInput.value) params.set('density', densityInput.value);
        
        if (page) params.set('page', page);
        return params;
    }

    function fetchTable(page) {
        const params = getParams(page);
        const url    = `{{ route('po.baseline') }}?${params.toString()}`;

        const tableWrapper = document.getElementById('tableWrapper');
        if (tableWrapper) tableWrapper.style.opacity = '0.4';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.text())
            .then(html => {
                const doc      = new DOMParser().parseFromString(html, 'text/html');
                const newTable = doc.getElementById('tableWrapper');

                if (newTable && tableWrapper) {
                    tableWrapper.innerHTML = newTable.innerHTML;
                    tableWrapper.style.opacity = '1';
                }

                const newInfo = doc.getElementById('dataInfo');
                const oldInfo = document.getElementById('dataInfo');
                if (newInfo && oldInfo) oldInfo.innerHTML = newInfo.innerHTML;

                window.history.replaceState(null, '', url);
                bindPaginationLinks();
            })
            .catch(err => {
                console.error('Fetch error:', err);
                if (tableWrapper) tableWrapper.style.opacity = '1';
            });
    }

    function bindPaginationLinks() {
        document.querySelectorAll('#paginationWrapper a').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const page = new URL(this.href).searchParams.get('page');
                if (page) fetchTable(page);
            });
        });
    }

    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => fetchTable(), 350);
        });
    }
    
    const densityInput = document.getElementById('density');
    if (densityInput) {
        densityInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => fetchTable(), 350);
        });
    }

    const perPageSelect = document.querySelector('select[name="per_page"]');
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function () {
            fetchTable();
        });
    }

    bindPaginationLinks();
});
</script>
