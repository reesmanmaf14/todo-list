{{-- A regular user's dashboard: their own task list (with Add Task) plus their counts --}}
@extends('tasks.index') 

@section('heading', 'My Dashboard')

@section('counts')
    @include('tasks.partials.task-counts')
@endsection
