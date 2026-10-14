@extends('backend.master-tailwind')
@section('title',__('operations.title'))
@section('content')
<x-backend.card :title="__('operations.title')">
<p class="mb-4 text-sm text-qpos-muted">{{ __('operations.notice') }}</p>
<dl class="grid gap-3 sm:grid-cols-2">
@foreach($status['heartbeats'] as $key=>$heartbeat)<div><dt class="font-semibold">{{ __('operations.'.$key) }}</dt><dd>{{ $heartbeat['at']??__('operations.unknown') }}@if($heartbeat['age_seconds']!==null) · {{ $heartbeat['age_seconds'] }} s @endif</dd></div>@endforeach
<div><dt class="font-semibold">{{ __('operations.queue') }}</dt><dd>{{ $status['pending_jobs']??__('operations.unknown') }} · {{ __('operations.queue_age') }} : {{ $status['oldest_job_age_seconds']??'-' }} s</dd></div>
<div><dt class="font-semibold">{{ __('operations.free') }}</dt><dd>{{ $status['free_bytes']!==null?number_format($status['free_bytes']/1048576,1,'.',' '):__('operations.unknown') }}</dd></div>
<div><dt class="font-semibold">{{ __('operations.rpo') }}</dt><dd>{{ $status['rpo_ok']?__('operations.ok'):__('operations.late') }} · {{ $status['backup_age_seconds']??'-' }} s</dd></div>
<div><dt class="font-semibold">{{ __('operations.rto') }}</dt><dd>{{ $status['rto_ok']===null?__('operations.unknown'):($status['rto_ok']?__('operations.ok'):__('operations.late')) }} · {{ $status['restored']->restore_seconds??'-' }} s</dd></div>
</dl>
<p class="mt-4 text-sm text-qpos-muted">{{ __('operations.measured_notice') }}</p>
<h2 class="mt-5 font-semibold">{{ __('operations.transport') }}</h2>
<ul class="mt-2 grid gap-2">
<li>{{ __('operations.daily') }} : {{ $status['daily_mail_enabled']?__('Yes'):__('No') }}</li>
<li>{{ __('operations.smtp') }} : {{ $status['smtp_ready']?__('external_alerts.ready'):__('external_alerts.unconfigured') }}</li>
@foreach($status['summary_states'] as $state=>$count)<li>{{ __('operations.daily') }} · {{ $state }} : {{ $count }}</li>@endforeach
@foreach($status['event_states'] as $state=>$count)<li>{{ __('notifications.title') }} · {{ $state }} : {{ $count }}</li>@endforeach
@foreach($status['external_states'] as $state=>$count)<li>{{ __('external_alerts.states.'.$state) }} : {{ $count }}</li>@endforeach
</ul>
</x-backend.card>
<x-backend.card :title="__('operations.history')">
<p class="mb-3 text-sm text-qpos-muted">{{ __('operations.archive_notice') }}</p>
<p class="mb-3 text-sm text-qpos-muted">{{ __('operations.consistency') }}</p>
<form method="post" action="{{ route('backend.admin.operations.settings') }}" class="grid gap-4 sm:grid-cols-2">@csrf
<label class="flex min-h-11 items-center gap-2"><input type="checkbox" name="enabled" value="1" @checked(old('enabled',$status['local_backup_enabled']))>{{ __('operations.local') }}</label>
<label class="flex min-h-11 items-center gap-2"><input type="checkbox" name="cloud_enabled" value="1" @checked(old('cloud_enabled',$status['cloud_enabled']))>{{ __('operations.cloud') }}</label>
<x-backend.input name="cloud_bucket" :label="__('operations.bucket')" :value="config('system.backup_cloud_bucket',config('filesystems.disks.backup_s3.bucket'))" />
<x-backend.input name="cloud_endpoint" type="url" :label="__('operations.endpoint')" :value="config('system.backup_cloud_endpoint',config('filesystems.disks.backup_s3.endpoint'))" />
<x-backend.input name="cloud_prefix" :label="__('operations.prefix')" :value="config('system.backup_cloud_prefix',config('backups.cloud_prefix'))" required />
<x-backend.input name="retention_days" type="number" min="7" max="3650" :label="__('operations.days')" :value="config('system.backup_retention_days',30)" required />
<label class="flex min-h-11 items-center gap-2"><input type="checkbox" name="retention_enabled" value="1" @checked(old('retention_enabled',config('system.backup_retention_enabled',false)))>{{ __('operations.retention') }}</label>
<p class="text-sm text-qpos-muted">{{ __('operations.retention_notice') }}</p>
<label class="sm:col-span-2 flex min-h-11 items-start gap-2"><input type="checkbox" class="mt-1" name="cloud_consent" value="1">{{ __('operations.cloud_consent') }}</label>
@error('cloud_consent')<p role="alert" class="text-qpos-danger sm:col-span-2">{{ $message }}</p>@enderror
<div><x-backend.button>{{ __('Save') }}</x-backend.button></div>
</form>
<form method="post" action="{{ route('backend.admin.operations.backup') }}" class="mt-5">@csrf<x-backend.button>{{ __('operations.run') }}</x-backend.button></form>
<p class="mt-3 text-sm text-qpos-muted">{{ __('operations.restore_help') }}</p>
@if($runs)
<div class="mt-4 overflow-x-auto"><table class="w-full text-left text-sm [&_th]:p-3 [&_td]:p-3 [&_td]:border-t [&_td]:border-qpos-border">
<thead><tr><th>#</th><th>{{ __('Date') }}</th><th>{{ __('operations.state') }}</th><th>{{ __('operations.cloud_state') }}</th><th>{{ __('operations.size') }}</th><th>{{ __('operations.restore') }}</th><th>{{ __('Actions') }}</th></tr></thead>
<tbody>@foreach($runs as $run)<tr><td>{{ $run->id }}</td><td>{{ \Carbon\CarbonImmutable::parse($run->started_at,'UTC')->setTimezone('Africa/Douala')->format('Y-m-d H:i:s') }}</td><td>{{ __('operations.states.'.$run->state) }}@if($run->last_error)<br><small>{{ $run->last_error }}</small>@endif</td><td>{{ __('operations.states.'.$run->cloud_state) }}</td><td>{{ $run->archive_bytes??'-' }}</td><td>{{ $run->restore_verified_at?\Carbon\CarbonImmutable::parse($run->restore_verified_at,'UTC')->setTimezone('Africa/Douala')->format('Y-m-d H:i:s'):'-' }}</td><td>@if($run->state==='verified')<a class="qpos-button qpos-button-sm qpos-button-secondary" href="{{ route('backend.admin.operations.download',$run->id) }}">{{ __('operations.download') }}</a>@endif</td></tr>@endforeach</tbody>
</table></div>{{ $runs->links() }}
@endif
</x-backend.card>
@endsection
