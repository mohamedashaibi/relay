<x-mail::message>
# Reset your password

Hi {{ $name }}, we received a request to reset the password for your Relay account.

<x-mail::button :url="$url">
Choose a new password
</x-mail::button>

This link expires in {{ $minutes }} minutes and can be used once. If you didn’t ask for a reset, you can ignore this email — your password stays the same.

<x-slot:subcopy>
If the button doesn’t work, paste this link into your browser: {{ $url }}
</x-slot:subcopy>
</x-mail::message>
