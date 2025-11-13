@props(['items' => [], 'active' => request()->path()])

<nav class="sidebar" aria-label="Левое меню">
    <div class="logo">
        <div class="mark">IT</div>
        <div class="brand">IT Staffer</div>
    </div>

    <ul class="nav">
        @foreach($items as $node)
            @include('components.parts.menu-node', ['node' => $node, 'active' => $active])
        @endforeach
    </ul>

    <div class="stick-bottom muted">v0.1 — меню</div>
</nav>
