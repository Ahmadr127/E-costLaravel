@props(['headers' => [], 'minWidth' => '1100px'])

<div class="table-responsive" style="overflow-x:auto; width:100%; max-width: 100%;">
    <div style="min-width: {{ $minWidth }}; width: max-content;">
        <table class="divide-y divide-gray-200" style="white-space: nowrap; width: 100%;">
            <thead class="bg-gray-50">
                <tr>
                    @foreach($headers as $header)
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
