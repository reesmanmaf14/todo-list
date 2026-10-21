{{-- The admin dashboard: every task (with Add Task and Assign to) plus totals across all users --}}
@extends('tasks.index')

@section('heading', 'Admin Dashboard')

@section('counts')
    @include('tasks.partials.task-counts', ['userCount' => $users->count()])
@endsection
