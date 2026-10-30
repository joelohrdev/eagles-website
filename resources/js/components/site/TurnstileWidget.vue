<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';

type TurnstileApi = {
    render: (
        container: HTMLElement,
        options: { sitekey: string; theme?: 'auto' | 'light' | 'dark' },
    ) => string;
    reset: (widgetId: string) => void;
    remove: (widgetId: string) => void;
};

declare global {
    interface Window {
        turnstile?: TurnstileApi;
    }
}

const props = defineProps<{
    siteKey: string;
}>();

const SCRIPT_URL =
    'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';

let scriptPromise: Promise<TurnstileApi> | null = null;

/** Load Cloudflare's script once per page load, however many widgets mount. */
function loadTurnstile(): Promise<TurnstileApi> {
    if (window.turnstile) {
        return Promise.resolve(window.turnstile);
    }

    scriptPromise ??= new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = SCRIPT_URL;
        script.async = true;
        script.onload = () =>
            window.turnstile
                ? resolve(window.turnstile)
                : reject(new Error('Turnstile failed to load'));
        script.onerror = () => {
            scriptPromise = null;
            reject(new Error('Turnstile failed to load'));
        };
        document.head.appendChild(script);
    });

    return scriptPromise;
}

const container = ref<HTMLElement | null>(null);
const failed = ref(false);
let widgetId: string | null = null;

onMounted(async () => {
    try {
        const turnstile = await loadTurnstile();

        if (container.value) {
            widgetId = turnstile.render(container.value, {
                sitekey: props.siteKey,
                theme: 'auto',
            });
        }
    } catch {
        failed.value = true;
    }
});

onBeforeUnmount(() => {
    if (widgetId) {
        window.turnstile?.remove(widgetId);
    }
});

/** Tokens are single use, so get a fresh one after every submit. */
function reset() {
    if (widgetId) {
        window.turnstile?.reset(widgetId);
    }
}

defineExpose({ reset });
</script>

<template>
    <div>
        <div ref="container" class="min-h-[65px]" />
        <p v-if="failed" class="text-sm text-destructive">
            The spam check didn't load. Turn off any content blocker for this
            site and reload the page.
        </p>
    </div>
</template>
