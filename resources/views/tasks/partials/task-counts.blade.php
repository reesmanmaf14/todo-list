{{-- Count cards at the top of both dashboards, counted from $tasks. $userCount: admin dashboard only --}}
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
    @foreach($cards as [$label, $count])
        <div class="col-6 {{ count($cards) === 4 ? 'col-md-3' : 'col-md-4' }}">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ $label }}</div>
                    <div class="fs-2 fw-semibold">{{ $count }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>
