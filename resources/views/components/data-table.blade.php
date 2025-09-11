@props(['headers', 'data', 'actions' => true, 'searchable' => true, 'filters' => []])

<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <!-- Header dengan Search dan Filter -->
    @if($searchable || count($filters) > 0)
    <div class="p-6 border-b border-gray-200">
        <form method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @if($searchable)
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Pencarian</label>
                    <input type="text" 
                           id="search" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Cari data..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>
                @endif

                @foreach($filters as $filter)
                <div>
                    <label for="{{ $filter['name'] }}" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ $filter['label'] }}
                    </label>
                    <select name="{{ $filter['name'] }}" 
                            id="{{ $filter['name'] }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">{{ $filter['placeholder'] ?? 'Semua' }}</option>
                        @foreach($filter['options'] as $value => $label)
                        <option value="{{ $value }}" {{ request($filter['name']) == $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endforeach
            </div>

            <div class="flex justify-between items-center">
                <button type="submit" 
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
                    <i class="fas fa-search mr-2"></i>Filter
                </button>
                
                @if(request()->hasAny(['search', 'status', 'kategori_id']))
                <a href="{{ request()->url() }}" 
                   class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                    <i class="fas fa-times mr-2"></i>Reset
                </a>
                @endif
            </div>
        </form>
    </div>
    @endif

    <!-- Table -->
    <div class="overflow-x-auto w-full max-w-full">
        <div style="min-width: 980px; width: max-content;">
            <table class="divide-y divide-gray-200 whitespace-nowrap w-full">
                <thead class="bg-gray-50">
                    <tr>
                        @foreach($headers as $header)
                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ $header }}
                        </th>
                        @endforeach
                        @if($actions)
                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Aksi
                        </th>
                        @endif
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 [&>tr>td]:px-4 [&>tr>td]:py-2">
                    @forelse($data as $item)
                    <tr class="hover:bg-gray-50">
                        {{ $slot }}
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ count($headers) + ($actions ? 1 : 0) }}" class="px-6 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                                <p class="text-lg font-medium">Tidak ada data</p>
                                <p class="text-sm">Belum ada data yang tersedia</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if($data->hasPages())
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $data->links() }}
    </div>
    @endif
</div>
