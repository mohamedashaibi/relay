<x-mail::message>
# Welcome to Relay, {{ $name }}

Relay is a task board your AI assistant can work on with you. Three things to try first:

- **Create a project.** Each one gets a short key, so tasks read like WEB-12.
- **Connect your assistant.** Add Relay to Claude or ChatGPT from the AI connections page.
- **Ask it what’s next.** “What’s on my plate today?” is a good start.

<x-mail::button :url="$appUrl">
Open Relay
</x-mail::button>

We’ll email you when tasks are assigned to you, need your review, or change status. You can turn any of these off in your account settings.

Questions? Reply to this email or write to [{{ $contact }}](mailto:{{ $contact }}).
</x-mail::message>
