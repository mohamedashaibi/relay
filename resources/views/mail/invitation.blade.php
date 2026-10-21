<x-mail::message>
# You’re invited to {{ $project }}

{{ $inviter }} added you to **{{ $project }}** on Relay, a task board your AI assistant can work on with you. Choose your name and a password to get started.

<x-mail::button :url="$url">
Accept the invite
</x-mail::button>

This link works once and expires in {{ $days }} days. If it runs out, ask {{ $inviter }} to send a new one. Weren’t expecting this? You can ignore this email.

<x-slot:subcopy>
If the button doesn’t work, paste this link into your browser: {{ $url }}
</x-slot:subcopy>
</x-mail::message>
