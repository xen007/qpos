<h1>{{ $title }}</h1>
@if($shop)<p>{{ __('Store') }} : #{{ $shop }}</p>@endif
@if($reference)<p>{{ __('external_alerts.reference') }} : #{{ $reference }}</p>@endif
@if($link)<p><a href="{{ $link }}">{{ __('external_alerts.authenticated_link') }}</a></p>@endif
<p>{{ __('external_alerts.minimal') }}</p>
