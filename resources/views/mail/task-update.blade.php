<x-mail::message>
# {{ $headline }}

@foreach ($lines as $line)
{{ $line }}

@endforeach
@if ($quote)
<x-mail::panel>
{{ $quote }}
</x-mail::panel>
@endif

<x-mail::button :url="$actionUrl">
{{ $actionText }}
</x-mail::button>

<x-slot:subcopy>
You’re getting this because “{{ $category }}” emails are on. [Change your email settings]({{ $settingsUrl }}).
</x-slot:subcopy>
</x-mail::message>
