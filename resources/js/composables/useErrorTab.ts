import type { Ref } from 'vue';
import { ref, watch } from 'vue';

export type ErrorTab = {
    /** The TabsTrigger/TabsContent value. */
    value: string;
    /** Exact field names this tab owns. */
    fields?: string[];
    /** Field-name prefix this tab owns, e.g. "seo" for seo.share_title. */
    prefix?: string;
};

function ownerOf(field: string, tabs: ErrorTab[]): ErrorTab | undefined {
    const owner = tabs.find(
        (tab) =>
            tab.fields?.includes(field) ||
            (tab.prefix !== undefined && field.startsWith(tab.prefix)),
    );

    /** A tab that claims no fields is the catch-all for everything else. */
    return owner ?? tabs.find((tab) => !tab.fields && tab.prefix === undefined);
}

/**
 * Active tab for a form whose fields span tabs.
 *
 * When the server rejects a submit, the message can land on a tab the user
 * isn't looking at, which reads as "Save did nothing". This activates the
 * earliest tab that owns an errored field. Pass tabs in render order.
 */
export function useErrorTab(
    errors: () => Record<string, string>,
    tabs: () => ErrorTab[],
): Ref<string> {
    const active = ref(tabs()[0].value);

    watch(
        () => Object.keys(errors() ?? {}),
        (fields) => {
            const order = tabs();
            const owners = fields
                .map((field) => ownerOf(field, order))
                .filter((tab): tab is ErrorTab => tab !== undefined);

            const first = order.find((tab) =>
                owners.some((owner) => owner.value === tab.value),
            );

            if (first) {
                active.value = first.value;
            }
        },
        { deep: true },
    );

    return active;
}
