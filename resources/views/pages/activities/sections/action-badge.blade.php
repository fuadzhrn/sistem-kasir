@php
    $actionTone = str_contains($activity['action'], 'failed') || str_contains($activity['action'], 'rejected')
        ? 'danger'
        : (str_contains($activity['action'], 'created') || str_contains($activity['action'], 'success') || str_contains($activity['action'], 'completed') || str_contains($activity['action'], 'approved') ? 'success' : 'info');
@endphp
<span class="badge {{ $actionTone === 'info' ? 'badge-info' : 'badge--'.$actionTone }}">{{ $activity['action_label'] }}</span>
