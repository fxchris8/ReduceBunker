@extends('layouts.app')

@section('title', 'Baseline Management')

@section('content')
<div class="container mx-auto py-6 px-4">
    <div class="bg-white rounded-lg shadow-md">
        <div class="bg-gray-50 px-4 py-4 border-b flex items-center justify-between">
            <h1 class="text-xl font-bold">Baseline Management</h1>
            <a href="{{ route('fuel-baseline.create') }}"
               class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm font-semibold">
                + Add Fuel Baseline
            </a>
        </div>

        <div class="p-6">
            @if(session('success'))
                <div class="bg-green-100 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <form method="GET" action="{{ route('fuel-baseline.index') }}" id="filterForm">
                <input type="hidden" name="per_page"  value="{{ $perPage }}">
                <input type="hidden" name="sort_by"   id="sortByInput"  value="{{ $sortBy }}">
                <input type="hidden" name="sort_dir"  id="sortDirInput" value="{{ $sortDir }}">

                <div class="flex items-center justify-between mb-4 gap-3">
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
                                value="{{ $search }}"
                                placeholder="Search vessel code / name..."
                                class="w-64 pl-9 pr-8 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-400">
                            @if($search)
                                <a href="{{ route('fuel-baseline.index', array_filter(['per_page' => $perPage, 'sort_by' => $sortBy, 'sort_dir' => $sortDir])) }}"
                                class="absolute inset-y-0 right-2 flex items-center text-gray-400 hover:text-gray-600 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                    </svg>
                                </a>
                            @endif
                        </div>
                        <div class="w-px h-5 bg-gray-300"></div>
                        <div class="relative">
                            <button type="button" onclick="toggleFilterDropdown()"
                                    class="flex items-center gap-2 border border-gray-300 rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                                </svg>
                                <span id="sortLabel">
                                    @php
                                        $sortLabels = ['updated_at' => 'Last Updated', 'vessel_id' => 'Vessel Code', 'vessel_name' => 'Vessel Name'];
                                    @endphp
                                    {{ $sortLabels[$sortBy] ?? 'Last Updated' }}
                                </span>
                                <span onclick="event.stopPropagation(); toggleSortDir()"
                                    title="Toggle sort direction"
                                    class="flex items-center text-blue-500 hover:text-blue-700 transition">
                                    @if($sortDir === 'asc')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/>
                                        </svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/>
                                        </svg>
                                    @endif
                                </span>
                            </button>
                            <div id="filterDropdown"
                                class="hidden absolute top-10 left-0 z-20 bg-white border border-gray-200 rounded-lg shadow-lg w-52 py-1">
                                <div class="flex items-center justify-between px-3 py-2 border-b border-gray-100">
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Sort By</span>
                                    <button type="button" onclick="closeFilterDropdown()"
                                            class="text-gray-400 hover:text-gray-600 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                        </svg>
                                    </button>
                                </div>
                                @foreach(['updated_at' => 'Last Updated', 'vessel_id' => 'Vessel Code', 'vessel_name' => 'Vessel Name'] as $value => $label)
                                    <button type="button"
                                            onclick="selectSort('{{ $value }}', '{{ $label }}')"
                                            class="w-full flex items-center justify-between px-4 py-2 text-sm hover:bg-blue-50 hover:text-blue-600 transition
                                                {{ $sortBy === $value ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-700' }}">
                                        <span>{{ $label }}</span>
                                        @if($sortBy === $value)
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20 6 9 17 4 12"/>
                                            </svg>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-gray-500">
                        <div class="flex items-center gap-2">
                            <span>Show</span>
                            <select name="per_page" onchange="document.getElementById('filterForm').submit()"
                                    class="border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400">
                                @foreach(['10', '25', '50', 'all'] as $option)
                                    <option value="{{ $option }}" {{ $perPage == $option ? 'selected' : '' }}>
                                        {{ $option === 'all' ? 'All' : $option }}
                                    </option>
                                @endforeach
                            </select>
                            <span>entries</span>
                        </div>
                        <div class="w-px h-4 bg-gray-300"></div>
                        <span id="dataInfo">
                            @if($isPaginated)
                                Showing {{ $fuelBaselines->firstItem() }}–{{ $fuelBaselines->lastItem() }} of {{ $fuelBaselines->total() }} entries
                            @else
                                Showing {{ $fuelBaselines->count() }} entries
                            @endif
                        </span>
                    </div>

                </div>
            </form>

            <div class="overflow-x-auto overflow-y-auto max-h-[80vh]" id="tableWrapper">
                <table class="w-full text-sm text-center border border-gray-200 rounded">
                    <thead class="bg-gray-100 text-gray-700 sticky top-0 z-10">
                        <tr>
                            <th rowspan="2" class="px-4 py-3 border border-gray-200 uppercase tracking-wide text-xs align-middle">Vessel Code</th>
                            <th rowspan="2" class="px-4 py-3 border border-gray-200 uppercase tracking-wide text-xs align-middle">Vessel Name</th>
                            <th colspan="4" class="px-4 py-3 border border-gray-200 uppercase tracking-wide text-xs text-center">Static Baseline</th>
                            <th colspan="1" class="px-4 py-3 border border-gray-200 uppercase tracking-wide text-xs text-center">Dynamic Baseline</th>
                            <th rowspan="2" class="px-4 py-3 border border-gray-200 uppercase tracking-wide text-xs align-middle">Density</th>
                            <th rowspan="2" class="px-4 py-3 border border-gray-200 uppercase tracking-wide text-xs align-middle">Speed (Knot)</th>
                            <th rowspan="2" class="px-4 py-3 border border-gray-200 uppercase tracking-wide text-xs align-middle">Safety Stock ME per Day</th>
                            <th rowspan="2" class="px-4 py-3 border border-gray-200 uppercase tracking-wide text-xs align-middle">Safety Stock AE per Day</th>
                            <th rowspan="2" class="px-4 py-3 border border-gray-200 uppercase tracking-wide text-xs align-middle">Last Updated</th>
                            <th rowspan="2" class="px-4 py-3 border border-gray-200 uppercase tracking-wide text-xs align-middle">Actions</th>
                        </tr>
                        <tr>
                            <th class="px-4 py-3 border border-gray-200 uppercase tracking-wide text-xs">ME (L/Hours)</th>
                            <th class="px-4 py-3 border border-gray-200 uppercase tracking-wide text-xs">AE (L/Hours)</th>
                            <th class="px-4 py-3 border border-gray-200 uppercase tracking-wide text-xs">AE 1 Reefer</th>
                            <th class="px-4 py-3 border border-gray-200 uppercase tracking-wide text-xs">AE Parallel 2</th>
                            <th class="px-4 py-3 border border-gray-200 uppercase tracking-wide text-xs">ME (L/Hours)</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200">
                        @forelse($fuelBaselines as $baseline)
                            <tr class="odd:bg-white even:bg-gray-50 hover:bg-blue-50">
                                <td class="px-4 py-3 border border-gray-200 font-semibold">
                                    {{ $baseline->vessel->vessel_id }}
                                </td>
                                <td class="px-4 py-3 border border-gray-200 text-left">
                                    {{ $baseline->vessel->vessel_name }}
                                </td>
                                {{-- Static Baseline --}}
                                <td class="px-4 py-3 border border-gray-200">
                                    {{ $baseline->static_bl_me }}
                                </td>
                                <td class="px-4 py-3 border border-gray-200">
                                    {{ $baseline->static_bl_ae }}
                                </td>
                                <td class="px-4 py-3 border border-gray-200">
                                    {{ $baseline->bl_ae_1_reffer ?? '-' }}
                                </td>
                                <td class="px-4 py-3 border border-gray-200">
                                    {{ $baseline->ae_parallel_2 ?? '-' }}
                                </td>
                                {{-- Dynamic Baseline --}}
                                <td class="px-4 py-3 border border-gray-200">
                                    {{ $baseline->dynamic_bl_me }}
                                </td>
                               
                                <td class="px-4 py-3 border border-gray-200">
                                    {{ $baseline->density }}
                                </td>
                                <td class="px-4 py-3 border border-gray-200">
                                    {{ $baseline->speed }}
                                </td>
                                <td class="px-4 py-3 border border-gray-200 font-semibold">
                                    {{ $baseline->ss_me }}
                                    <span class="text-xs text-gray-400 font-normal">
                                        ({{ $baseline->ss_multiplier_me }}x)
                                    </span>
                                </td>
                                <td class="px-4 py-3 border border-gray-200 font-semibold">
                                    {{ $baseline->ss_ae }}
                                    <span class="text-xs text-gray-400 font-normal">
                                        ({{ $baseline->ss_multiplier_ae }}x)
                                    </span>
                                </td>
                                <td class="px-4 py-3 border border-gray-200 text-gray-500 text-xs">
                                    {{ ($baseline->updated_at ?? $baseline->created_at)?->format('d M Y H:i') ?? '-' }}
                                </td>
                                <td class="px-4 py-3 border border-gray-200">
                                    <div class="flex justify-center gap-2">
                                        <a href="{{ route('fuel-baseline.edit', $baseline) }}"
                                           class="text-yellow-500 hover:text-yellow-600 p-1 rounded hover:bg-yellow-50 transition"
                                           title="Edit">
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                 class="w-5 h-5"
                                                 viewBox="0 0 24 24"
                                                 fill="none"
                                                 stroke="currentColor"
                                                 stroke-width="2"
                                                 stroke-linecap="round"
                                                 stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                        </a>

                                        <form
                                            action="{{ route('fuel-baseline.destroy', $baseline) }}"
                                            method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete the baseline for vessel {{ $baseline->vessel->vessel_id }}?')"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="text-red-500 hover:text-red-600 p-1 rounded hover:bg-red-50 transition"
                                                title="Delete"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                     class="w-5 h-5"
                                                     viewBox="0 0 24 24"
                                                     fill="none"
                                                     stroke="currentColor"
                                                     stroke-width="2"
                                                     stroke-linecap="round"
                                                     stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"/>
                                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                                    <path d="M10 11v6M14 11v6"/>
                                                    <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="px-4 py-8 text-gray-400 text-center">
                                    No fuel baseline data available.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div id="paginationWrapper" class="mt-4 flex flex-col items-center gap-2">
                    @if($isPaginated && $fuelBaselines->hasPages())
                        {{ $fuelBaselines->links() }}
                    @endif
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
        params.set('search',   document.getElementById('searchInput').value);
        params.set('sort_by',  document.getElementById('sortByInput').value);
        params.set('sort_dir', document.getElementById('sortDirInput').value);
        params.set('per_page', document.querySelector('select[name="per_page"]').value);
        if (page) params.set('page', page);
        return params;
    }

    function fetchTable(page) {
        const params = getParams(page);
        const url    = `{{ route('fuel-baseline.index') }}?${params.toString()}`;

        document.getElementById('tableWrapper').style.opacity = '0.4';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.text())
            .then(html => {
                const doc      = new DOMParser().parseFromString(html, 'text/html');
                const newTable = doc.getElementById('tableWrapper');

                if (newTable) {
                    document.getElementById('tableWrapper').innerHTML = newTable.innerHTML;
                    document.getElementById('tableWrapper').style.opacity = '1';
                }

                const newInfo = doc.getElementById('dataInfo');
                const oldInfo = document.getElementById('dataInfo');
                if (newInfo && oldInfo) oldInfo.innerHTML = newInfo.innerHTML;

                window.history.replaceState(null, '', url);
                bindPaginationLinks();
            })
            .catch(err => {
                console.error('Fetch error:', err);
                document.getElementById('tableWrapper').style.opacity = '1';
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

    document.getElementById('searchInput').addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => fetchTable(), 350);
    });

    document.querySelector('select[name="per_page"]').addEventListener('change', function () {
        fetchTable();
    });

    document.addEventListener('click', function (e) {
        const dropdown = document.getElementById('filterDropdown');
        if (dropdown && !dropdown.closest('.relative').contains(e.target)) {
            closeFilterDropdown();
        }
    });

    bindPaginationLinks();

    window._fetchTable = fetchTable;
});

function toggleFilterDropdown() {
    document.getElementById('filterDropdown').classList.toggle('hidden');
}

function closeFilterDropdown() {
    document.getElementById('filterDropdown').classList.add('hidden');
}

function selectSort(value, label) {
    document.getElementById('sortByInput').value     = value;
    document.getElementById('sortLabel').textContent = label;
    closeFilterDropdown();
    window._fetchTable();
}

function toggleSortDir() {
    const input = document.getElementById('sortDirInput');
    input.value = input.value === 'asc' ? 'desc' : 'asc';

    const span = document.querySelector('[onclick*="toggleSortDir"]');
    span.innerHTML = input.value === 'asc'
        ? `<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>`
        : `<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>`;

    window._fetchTable();
}
</script>
