{{-- Count cards at the top of both dashboards. Counted from the $tasks already on the page (no extra queries);
     JavaScript recounts them after every change (see updateCounts in tasks/index). $userCount: admin dashboard only --}}
@php
    $cards = [
        'total' => ['Total tasks', $tasks->count()],
        'pending' => ['Pending', $tasks->where('status', 'Pending')->count()],
        'completed' => ['Completed', $tasks->where('status', 'Completed')->count()],
    ];

    if (isset($userCount)) {
        $cards = ['users' => ['Users', $userCount]] + $cards;
    }
@endphp

<div class="row g-3 mb-4">
    @foreach($cards as $key => [$label, $count])
        <div class="col-6 {{ count($cards) === 4 ? 'col-md-3' : 'col-md-4' }}">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ $label }}</div>
                    <div class="fs-2 fw-semibold" data-count="{{ $key }}">{{ $count }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>
