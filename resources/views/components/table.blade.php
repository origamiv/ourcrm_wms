<div class="card">
    @if($title)
        <div class="title-row" style="padding: 12px; border-bottom: 1px solid var(--line);">
            <h2>{{ $title }}</h2>
        </div>
    @endif

    <div class="table-wrap">
        <table>
                      <thead>
            <tr>
                @foreach($columns as $col)
                    <th>{{ $col['label'] }}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($columns as $col)
                        @php
                        $row=(array)$row;
                        $field=$col['key']
                        @endphp
                        <td>{{ $row[$field] ?? '' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" class="muted">Нет данных</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
