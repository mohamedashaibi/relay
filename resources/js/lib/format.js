export const STATUSES = [
    { value: 'backlog', label: 'Backlog', color: 'var(--st-backlog)' },
    { value: 'todo', label: 'To do', color: 'var(--st-todo)' },
    { value: 'in_progress', label: 'In progress', color: 'var(--st-progress)' },
    { value: 'in_review', label: 'Needs review', color: 'var(--st-review)' },
    { value: 'done', label: 'Done', color: 'var(--st-done)' },
];

export const PRIORITIES = [
    { value: 'urgent', label: 'Urgent', bars: 4 },
    { value: 'high', label: 'High', bars: 3 },
    { value: 'medium', label: 'Medium', bars: 2 },
    { value: 'low', label: 'Low', bars: 1 },
];

export const SWATCHES = ['#0F766E', '#C2410C', '#4338CA', '#B91C1C', '#0369A1', '#7E22CE', '#15803D', '#A16207'];

export const statusMeta =(v) => STATUSES.find((s) => s.value === v) ?? STATUSES[1];
export const priorityMeta = (v) => PRIORITIES.find((p) => p.value === v) ?? PRIORITIES[2];

const rtf = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' });

export function ago(iso) {
    if (!iso) return '';
    const seconds = (new Date(iso).getTime() - Date.now()) / 1000;
    const units = [
        ['year', 31536000],
        ['month', 2592000],
        ['week', 604800],
        ['day', 86400],
        ['hour', 3600],
        ['minute', 60],
    ];
    for (const [unit, size] of units) {
        if (Math.abs(seconds) >= size) return rtf.format(Math.round(seconds / size), unit);
    }
    return 'just now';
}

/** "Today", "Tomorrow", "Mar 4" — plus whether it's overdue. */
export function due(dateStr, status) {
    if (!dateStr) return null;
    const [y, m, d] = dateStr.split('-').map(Number);
    const date = new Date(y, m - 1, d);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const days = Math.round((date - today) / 86400000);
    const label =
        days === 0
            ? 'Today'
            : days === 1
              ? 'Tomorrow'
              : days === -1
                ? 'Yesterday'
                : date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', ...(y !== today.getFullYear() && { year: 'numeric' }) });
    return { label, overdue: days < 0 && status !== 'done', soon: days >= 0 && days <= 2 };
}

export function fileSize(bytes = 0) {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1048576) return `${Math.round(bytes / 1024)} KB`;
    return `${(bytes / 1048576).toFixed(bytes < 10485760 ? 1 : 0)} MB`;
}

export function initials(name = '') {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p[0].toUpperCase())
        .join('');
}

const HUES = [172, 24, 230, 280, 140, 340, 200, 45];
export function hue(id = 0) {
    return HUES[id % HUES.length];
}

export function greeting() {
    const h = new Date().getHours();
    return h < 5 ? 'Working late' : h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening';
}

export function firstName(name = '') {
    return name.split(/\s+/)[0] || name;
}
