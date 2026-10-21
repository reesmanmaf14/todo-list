@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Profile</h2>

    <a href="{{ route('tasks.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Tasks
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="card shadow-sm border-danger">
            <div class="card-body">
                @include('profile.partials.delete-user-form')
            </div>
        </div>

    </div>
</div>

@endsection
