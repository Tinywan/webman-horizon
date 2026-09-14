<template>
  <span
    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-semibold font-mono border"
    :class="tones[tone]"
  >
    <slot>{{ value }}</slot>
  </span>
</template>

<script setup>
defineProps({
  value: { type: [Number, String], default: '' },
  tone: { type: String, default: 'indigo' }, // indigo | amber | rose | emerald | gray
});

const tones = {
  indigo: 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20',
  amber: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
  rose: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
  emerald: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
  gray: 'bg-surface-strong text-ink-muted border-line-strong',
};
</script>
