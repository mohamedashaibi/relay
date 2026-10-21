import axios from 'axios';

// Same-origin session API. Axios forwards Laravel's XSRF-TOKEN cookie as X-XSRF-TOKEN automatically.
export const api = axios.create({
    baseURL: '/api',
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    withCredentials: true,
});

api.interceptors.response.use(
    (res) => res,
    (error) => {
        if (error.response?.status === 401 && !location.pathname.startsWith('/login')) {
            location.assign(`/login?next=${encodeURIComponent(location.pathname + location.search)}`);
        }
        return Promise.reject(error);
    },
);

/** Human-readable message + per-field errors from a Laravel error response. */
export function problem(error) {
    const data = error?.response?.data ?? {};
    const fields = Object.fromEntries(Object.entries(data.errors ?? {}).map(([k, v]) => [k, v[0]]));
    let message = data.message || 'Something went wrong. Please try again.';
    if (error?.response?.status === 419) message = 'Your session expired. Refresh the page and try again.';
    if (!error?.response) message = 'Can’t reach the server. Check your connection.';
    return { message, fields };
}
