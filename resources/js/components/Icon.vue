<script setup>
// Hand-drawn 24px stroke icons (Lucide-style). Decorative by default: label the parent control instead.
defineProps({
    name: { type: String, required: true },
    size: { type: [Number, String], default: 18 },
});

const PATHS = {
    home: ['M3 11.5 12 4l9 7.5', 'M5 10v10h5v-6h4v6h5V10'],
    review: ['m8.5 12 2.5 2.5 4.5-5', 'M20 12a8 8 0 1 1-16 0 8 8 0 0 1 16 0z'],
    folder: ['M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z'],
    plug: ['M9 2v5', 'M15 2v5', 'M6 7h12v4a6 6 0 0 1-12 0z', 'M12 17v5'],
    plus: ['M12 5v14', 'M5 12h14'],
    search: ['M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14z', 'm20 20-4-4'],
    x: ['M18 6 6 18', 'M6 6l12 12'],
    'chevron-down': ['m6 9 6 6 6-6'],
    'chevron-right': ['m9 6 6 6-6 6'],
    more: ['M5 12h.01', 'M12 12h.01', 'M19 12h.01'],
    sparkles: ['M12 3.5 13.7 8l4.8 1.7-4.8 1.8L12 16l-1.7-4.5-4.8-1.8L10.3 8z', 'M18.5 15.5l.6 1.4 1.4.6-1.4.6-.6 1.4-.6-1.4-1.4-.6 1.4-.6z'],
    message: ['M20 11.5a7.5 7.5 0 0 1-11 6.6L4 19.5l1.4-4.6A7.5 7.5 0 1 1 20 11.5z'],
    calendar: ['M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z', 'M16 3v4', 'M8 3v4', 'M4 11h16'],
    trash: ['M4 7h16', 'M9 7V4h6v3', 'M6 7l1 13h10l1-13'],
    sliders: ['M4 7h9', 'M17 7h3', 'M15 5v4', 'M4 17h3', 'M11 17h9', 'M9 15v4'],
    logout: ['M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3', 'M10 17l5-5-5-5', 'M15 12H3'],
    sun: ['M12 16a4 4 0 1 0 0-8 4 4 0 0 0 0 8z', 'M12 2v2', 'M12 20v2', 'm4.9 4.9 1.4 1.4', 'm17.7 17.7 1.4 1.4', 'M2 12h2', 'M20 12h2', 'm4.9 19.1 1.4-1.4', 'm17.7 6.3 1.4-1.4'],
    moon: ['M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z'],
    monitor: ['M3 5a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z', 'M8 21h8', 'M12 17v4'],
    menu: ['M4 7h16', 'M4 12h16', 'M4 17h16'],
    copy: ['M9 9h11v11H9z', 'M5 15H4V4h11v1'],
    check: ['m5 12.5 4.5 4.5L19 7.5'],
    'arrow-left': ['M19 12H5', 'm11 18-6-6 6-6'],
    'arrow-right': ['M5 12h14', 'm13 6 6 6-6 6'],
    external: ['M14 4h6v6', 'M20 4l-9 9', 'M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5'],
    key: ['M7.5 15.5a4 4 0 1 1 3.6-5.7H21v3h-2v3h-3v-3h-4.9a4 4 0 0 1-3.6 2.7z'],
    terminal: ['m5 17 5-5-5-5', 'M12 19h7'],
    alert: ['M12 9v4', 'M12 17h.01', 'M10.3 4 2 18.5A2 2 0 0 0 3.7 21h16.6a2 2 0 0 0 1.7-2.5L13.7 4a2 2 0 0 0-3.4 0z'],
    user: ['M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8z', 'M4.5 21a7.5 7.5 0 0 1 15 0'],
    archive: ['M3 4h18v4H3z', 'M5 8v12h14V8', 'M10 12h4'],
    paperclip: ['M20.5 11.5 12 20a5 5 0 0 1-7-7l8.5-8.5a3.3 3.3 0 0 1 4.7 4.7l-8.5 8.5a1.7 1.7 0 0 1-2.4-2.4L15 7.6'],
    upload: ['M12 15V4', 'm7 9 5-5 5 5', 'M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3'],
    link: ['M10 14a4.5 4.5 0 0 0 6.4 0l3-3a4.5 4.5 0 0 0-6.4-6.4l-1 1', 'M14 10a4.5 4.5 0 0 0-6.4 0l-3 3a4.5 4.5 0 0 0 6.4 6.4l1-1'],
};
</script>

<template>
    <svg
        :width="size"
        :height="size"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        :stroke-width="name === 'more' ? 3 : 1.75"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
        focusable="false"
        class="shrink-0"
    >
        <path v-for="d in PATHS[name] ?? []" :key="d" :d="d" />
    </svg>
</template>
