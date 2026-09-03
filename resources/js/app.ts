import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Eagles Baseball Travel';

createInertiaApp({
    // Public pages get a fully-formatted title from the server `seo` prop; admin pages get the app name appended.
    title: (title) =>
        !title
            ? appName
            : title.includes(appName)
              ? title
              : `${title} - ${appName}`,
    layout: (name) => {
        switch (true) {
            case name.startsWith('site/'):
                return PublicLayout;
            case name.startsWith('auth/'):
            case name.startsWith('invitations/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#6D96B6',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
