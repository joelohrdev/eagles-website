<script setup lang="ts">
import type { TabsRootEmits, TabsRootProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { TabsRoot, useForwardPropsEmits } from "reka-ui"
import { onMounted, ref } from "vue"
import { cn } from "@/lib/utils"

/**
 * `unmountOnHide` defaults to false (reka-ui defaults it to true): our tabbed
 * admin forms span tabs, and an unmounted panel's inputs are absent from the
 * DOM, so submitting from one tab silently dropped every other tab's fields.
 * Hidden panels stay mounted with the `hidden` attribute instead.
 */
const props = withDefaults(
  defineProps<TabsRootProps & { class?: HTMLAttributes["class"] }>(),
  { unmountOnHide: false },
)
const emits = defineEmits<TabsRootEmits>()

const delegatedProps = reactiveOmit(props, "class")
const forwarded = useForwardPropsEmits(delegatedProps, emits)

const tabsRoot = ref<{ $el?: HTMLElement } | null>(null)

/**
 * A hidden panel's `required` input is unfocusable, and browsers abort the
 * submit without reporting anything when they hit one — the same silent
 * "Save did nothing" this component used to cause by unmounting panels. Let
 * the server be the validator; useErrorTab opens the tab holding the error.
 */
onMounted(() => {
  tabsRoot.value?.$el?.closest("form")?.setAttribute("novalidate", "")
})
</script>

<template>
  <TabsRoot
    ref="tabsRoot"
    v-slot="slotProps"
    data-slot="tabs"
    v-bind="forwarded"
    :class="cn('flex flex-col gap-2', props.class)"
  >
    <slot v-bind="slotProps" />
  </TabsRoot>
</template>
