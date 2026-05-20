<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="close">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black/40 transition-opacity" />

    <!-- Modal panel -->
    <div class="relative w-full max-w-lg transform rounded-xl bg-white shadow-xl transition-all">
      <!-- Header -->
      <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
        <h3 class="text-lg font-semibold text-gray-900">{{ title }}</h3>
        <button
          class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-500 transition"
          @click="close"
        >
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Body -->
      <div class="max-h-[70vh] overflow-y-auto px-6 py-4">
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['close'])

function close() {
  emit('close')
}
</script>
