@extends('layouts.app')

@section('title', 'Detail Aktivitas')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/activities.css') }}">
@endpush

@section('content')
    @php
        $metadata = $activity['metadata'];
        $beforeData = is_array($metadata['before'] ?? null) ? $metadata['before'] : [];
        $afterData = is_array($metadata['after'] ?? null) ? $metadata['after'] : [];
        $metadataChanges = is_array($metadata['changes'] ?? null) ? $metadata['changes'] : [];
        $remainingChanges = [];

        foreach ($metadataChanges as $field => $change) {
            if (! is_array($change) || (! array_key_exists('before', $change) && ! array_key_exists('after', $change))) {
                $remainingChanges[$field] = $change;
                continue;
            }

            $beforeData[$field] = $change['before'] ?? null;
            $afterData[$field] = $change['after'] ?? null;

            $remainingChangeValues = \Illuminate\Support\Arr::except($change, ['before', 'after']);
            if ($remainingChangeValues !== []) {
                $remainingChanges[$field] = $remainingChangeValues;
            }
        }

        $hasChangeData = $beforeData !== [] || $afterData !== [];
        $metadataToDisplay = \Illuminate\Support\Arr::except($metadata, ['before', 'after', 'changes']);

        if ($remainingChanges !== []) {
            $metadataToDisplay['changes'] = $remainingChanges;
        }
    @endphp

    <div class="activities-page activities-detail-page" data-activities-detail-page>
        @include('pages.activities.sections.detail-header')

        <div class="activities-detail-grid">
            @include('pages.activities.sections.actor')
            @include('pages.activities.sections.reference')
            @include('pages.activities.sections.activity-change-data')
            @include('pages.activities.sections.metadata', ['metadata' => $metadataToDisplay])
            @include('pages.activities.sections.security-context')
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/activities.js') }}" defer></script>
@endpush
