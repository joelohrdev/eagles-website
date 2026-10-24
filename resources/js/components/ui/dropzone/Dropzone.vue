<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { ref } from "vue"
import { cn } from "@/lib/utils"

/**
 * Drag-and-drop file zone backed by a real file input, so files submit
 * natively with the enclosing <form> (Inertia <Form> reads its FormData).
 * The zone is the input's <label>: clicking it opens the picker and the
 * visually hidden input stays keyboard focusable.
 */
const props = withDefaults(
  defineProps<{
    id: string
    name: string
    accept?: string
    multiple?: boolean
    disabled?: boolean
    invalid?: boolean
    class?: HTMLAttributes["class"]
  }>(),
  {
    accept: undefined,
    multiple: false,
    disabled: false,
    invalid: false,
    class: undefined,
  },
)

const emits = defineEmits<{
  (e: "select", files: File[]): void
}>()

const input = ref<HTMLInputElement | null>(null)
const isDragging = ref(false)
let dragDepth = 0

function isAccepted(file: File): boolean {
  if (!props.accept) {
    return true
  }

  return props.accept.split(",").some((rule) => {
    const type = rule.trim().toLowerCase()

    if (type.startsWith(".")) {
      return file.name.toLowerCase().endsWith(type)
    }

    if (type.endsWith("/*")) {
      return file.type.startsWith(type.slice(0, -1))
    }

    return file.type === type
  })
}

function onDragEnter() {
  if (props.disabled) {
    return
  }

  dragDepth++
  isDragging.value = true
}

function onDragLeave() {
  dragDepth = Math.max(0, dragDepth - 1)

  if (dragDepth === 0) {
    isDragging.value = false
  }
}

function onDrop(event: DragEvent) {
  dragDepth = 0
  isDragging.value = false

  if (props.disabled || !input.value || !event.dataTransfer) {
    return
  }

  let files = Array.from(event.dataTransfer.files).filter(isAccepted)

  if (!props.multiple) {
    files = files.slice(0, 1)
  }

  if (files.length === 0) {
    return
  }

  const transfer = new DataTransfer()
  files.forEach((file) => transfer.items.add(file))
  input.value.files = transfer.files

  emits("select", files)
}

function onChange() {
  emits("select", Array.from(input.value?.files ?? []))
}

function clear() {
  if (input.value) {
    input.value.value = ""
  }
}

defineExpose({ clear })
</script>

<template>
  <label
    :for="id"
    data-slot="dropzone"
    :data-dragging="isDragging || undefined"
    :aria-disabled="disabled || undefined"
    :class="cn(
      'border-input text-muted-foreground relative flex cursor-pointer flex-col items-center justify-center gap-2 overflow-hidden rounded-md border-2 border-dashed bg-muted/40 p-4 text-center text-sm transition-[color,box-shadow,border-color,background-color]',
      'hover:border-primary/60 hover:bg-muted/70',
      'focus-within:border-ring focus-within:ring-ring/50 focus-within:ring-[3px]',
      'data-[dragging]:border-primary data-[dragging]:bg-primary/10',
      'aria-disabled:pointer-events-none aria-disabled:opacity-50',
      invalid && 'border-destructive',
      props.class,
    )"
    @dragenter.prevent="onDragEnter"
    @dragover.prevent
    @dragleave.prevent="onDragLeave"
    @drop.prevent="onDrop"
  >
    <input
      :id="id"
      ref="input"
      type="file"
      :name="name"
      :accept="accept"
      :multiple="multiple"
      :disabled="disabled"
      :aria-invalid="invalid || undefined"
      class="sr-only"
      @change="onChange"
    >
    <slot :is-dragging="isDragging" />
  </label>
</template>
