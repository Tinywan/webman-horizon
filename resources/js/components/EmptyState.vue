<template>
  <div class="flex flex-col items-center justify-center py-16 px-6 text-center">
    <div class="w-14 h-14 rounded-2xl bg-surface border border-line flex items-center justify-center text-ink-faint">
      <component :is="icon" :size="26" :stroke-width="1.5" />
    </div>
    <h3 class="mt-5 text-sm font-semibold text-ink-secondary">{{ title }}</h3>
    <p class="mt-1.5 text-xs text-ink-faint max-w-sm leading-relaxed">{{ description }}</p>
    <div v-if="$slots.default" class="mt-5">
      <slot></slot>
    </div>
  </div>
</template>

<script setup>
defineProps({
  icon: { type: [Object, Function], required: true },
  title: { type: String, required: true },
  description: { type: String, default: '' },
});
</script>
