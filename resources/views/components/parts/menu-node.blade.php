@php
    $path = ltrim(parse_url($node['url'] ?? '#', PHP_URL_PATH) ?? '', '/');
    $isActive = $path && request()->is($path);
    $hasChildren = !empty($node['children']);
@endphp

<li>
    <a href="{{ $node['url'] ?: '#' }}" class="{{ $isActive ? 'active' : '' }}">
    <span class="ico">
      @php $icon = $node['icon'] ?? ''; @endphp
        @if(\Illuminate\Support\Str::startsWith($icon, ['ti ','ti-','fa ','fa-','mdi ','mdi-', 'uil', 'uil-']))
            <i class="{{ $icon }}"></i>
        @else
            {{ $icon ?: '•' }}
        @endif
    </span>
        <span>{{ $node['name'] }}</span>
        @if(!empty($node['badge'])) <span class="badge">{{ $node['badge'] }}</span> @endif
    </a>

    @if($hasChildren)
        <ul class="nav" style="padding-left:12px">
            @foreach($node['children'] as $child)
                @include('components.parts.menu-node', ['node' => $child, 'active' => $active])
            @endforeach
        </ul>
    @endif
</li>
