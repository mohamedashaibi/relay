<x-mail::message>
# Your password was changed

Hi {{ $name }}, the password for your Relay account ({{ $email }}) was changed on {{ $changedAt }}.

**If this was you**, there’s nothing else to do.

**If it wasn’t**, reset your password right away and review your AI connections — assistants you connected keep working until you revoke them.

<x-mail::button :url="$forgotUrl">
Reset my password
</x-mail::button>

Need help? Write to [{{ $contact }}](mailto:{{ $contact }}).
</x-mail::message>
