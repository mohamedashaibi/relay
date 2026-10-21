<?php

return [
    'meta' => [
        'title' => 'Relay — Task management for people who work with AI',
        'description' => 'Relay is a task board your AI assistant can use. Connect Claude or ChatGPT over MCP to fetch your tasks, update their status and hand work back for review.',
    ],

    'nav' => [
        'features' => 'Features',
        'how' => 'How it works',
        'clients' => 'Integrations',
        'faq' => 'FAQ',
        'sign_in' => 'Sign in',
        'start' => 'Get started',
        'open_app' => 'Open Relay',
        'menu' => 'Menu',
        'switch' => 'العربية',
        'switch_label' => 'اقرأ هذه الصفحة بالعربية',
    ],

    'hero' => [
        'eyebrow' => 'Task management built for AI assistants',
        'title' => 'Your assistant already knows what’s next.',
        'lead' => 'Relay is a task board your AI can actually use. Connect Claude or ChatGPT over MCP — it fetches your work, moves tasks forward, and hands them back to you for review.',
        'primary' => 'Get started',
        'secondary' => 'Sign in',
        'note' => 'Set up in about two minutes.',
    ],

    'mock' => [
        'label' => 'Example: an assistant working through Relay',
        'you' => 'you',
        'ai' => 'ai',
        'lines' => [
            ['who' => 'you', 'text' => 'What’s on my plate today?'],
            ['who' => 'tool', 'text' => 'get_my_work'],
            ['who' => 'ai', 'text' => 'Three tasks. WEB-12 “Ship the pricing page” is in progress and due tomorrow — want to start there?'],
            ['who' => 'you', 'text' => 'Yes. When it’s done, send it to Sam for review.'],
            ['who' => 'tool', 'text' => 'request_review  WEB-12 → Sam'],
        ],
        'columns' => ['In progress', 'Needs review', 'Done'],
        'cards' => [
            ['key' => 'WEB-12', 'title' => 'Ship the pricing page'],
            ['key' => 'WEB-9', 'title' => 'Review the homepage copy'],
            ['key' => 'WEB-7', 'title' => 'Choose brand typography'],
        ],
        'via' => 'via Claude',
    ],

    'clients' => [
        'title' => 'Works with',
        'any' => 'Any MCP client',
    ],

    'features' => [
        'eyebrow' => 'Features',
        'title' => 'Everything a team needs. Built so AI can help.',
        'items' => [
            ['title' => 'Speaks MCP natively', 'body' => 'Thirteen purpose-built tools let assistants read your queue and the files attached to it — specs, PDFs, screenshots — create and update tasks, comment, save their own reports as files, and request reviews. All as you, with your permissions.'],
            ['title' => 'Human review, built in', 'body' => 'When work is ready, the assistant hands it back with a summary. Only the named reviewer or a project admin can approve, so an AI can’t sign off its own work.'],
            ['title' => 'A board that moves fast', 'body' => 'Drag-and-drop kanban, inline editing, Markdown descriptions, labels, due dates and priorities. Every action also works from the keyboard.'],
            ['title' => 'A clear audit trail', 'body' => 'Every change is logged. Anything an assistant does is marked “via Claude” or “via ChatGPT”, so you always know who did what.'],
            ['title' => 'Secure by default', 'body' => 'OAuth consent for Claude.ai and ChatGPT, revocable personal tokens for developer tools, and per-project roles for owners, admins and members.'],
            ['title' => 'Made for focus', 'body' => 'A home view of what’s yours, a review queue, instant search with ⌘K, and light and dark themes.'],
        ],
    ],

    'how' => [
        'eyebrow' => 'How it works',
        'title' => 'Up and running in three steps.',
        'steps' => [
            ['title' => 'Create a project', 'body' => 'Each project gets a short key, so tasks read like WEB-12 — easy for you and your assistant to reference.'],
            ['title' => 'Connect your assistant', 'body' => 'Paste one URL into Claude or ChatGPT and approve access. Developers can use a personal token in Claude Code or Cursor.'],
            ['title' => 'Work together', 'body' => 'Ask “what’s on my plate?” Your assistant picks up tasks, posts progress, and sends finished work to you for review.'],
        ],
    ],

    'prompts' => [
        'eyebrow' => 'Try it',
        'title' => 'Things you can ask.',
        'items' => [
            'What’s on my plate today? Start with anything urgent.',
            'Pick up WEB-12, post a plan as a comment, then move it to in progress.',
            'I’ve finished the pricing page — send WEB-4 for review with a summary of what changed.',
            'Create tasks in OPS for each item in this incident report and assign them to me.',
        ],
    ],

    'faq' => [
        'eyebrow' => 'FAQ',
        'title' => 'Questions, answered.',
        'items' => [
            ['q' => 'Which assistants work with Relay?', 'a' => 'Claude (on the web, desktop and in Claude Code), ChatGPT in developer mode, Cursor, VS Code, and any client that supports remote MCP servers.'],
            ['q' => 'Can an assistant see everything in my account?', 'a' => 'It sees exactly what you see: only the projects you’re a member of. You can revoke any connection instantly from the AI connections page.'],
            ['q' => 'Can the AI mark its own work as done?', 'a' => 'Not when someone else is the reviewer. Tasks in review can only be approved by the named reviewer or a project admin.'],
            ['q' => 'Do I need to know how to code?', 'a' => 'No. Connecting Claude or ChatGPT takes a URL and one click to approve. Personal tokens are there for developers who want them.'],
            ['q' => 'Does Relay support Arabic?', 'a' => 'This website is in Arabic and English, and your assistant understands both — so you can ask about your tasks in Arabic. The app interface is currently in English.'],
        ],
    ],

    'cta' => [
        'title' => 'Give your assistant a seat on the team.',
        'lead' => 'Create your account in a minute, then invite your team and connect your assistant.',
        'button' => 'Create your account',
    ],

    'footer' => [
        'by' => 'Relay by Tanami',
        'contact' => 'Contact',
        'rights' => 'All rights reserved.',
    ],
];
