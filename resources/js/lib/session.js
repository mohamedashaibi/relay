import { reactive } from 'vue';
import { api } from './api';

export const session = reactive({
    user: window.__RELAY__?.user ?? null,
});

export async function login(payload) {
    const { data } = await api.post('/auth/login', payload);
    session.user = data.user;
    return data;
}

export async function acceptInvite(payload) {
    const { data } = await api.post('/auth/accept-invite', payload);
    session.user = data.user;
    return data;
}

export async function logout() {
    await api.post('/auth/logout');
    session.user = null;
    location.assign('/');
}
