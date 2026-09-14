<template>
  <div class="card card-hover p-5">
    <div class="flex items-start justify-between">
      <div class="text-xs font-medium text-ink-faint uppercase tracking-wider">
        {{ label }}
      </div>
      <div
        v-if="icon"
        class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
        :class="tones[tone].iconWrap"
      >
        <component :is="icon" :size="16" :stroke-width="2" />
      </div>
    </div>
    <div class="mt-3 text-3xl font-bold font-mono tracking-tight" :class="tones[tone].value">
      {{ displayValue }}
    </div>
    <div v-if="hint" class="mt-2 text-xs text-ink-faint">{{ hint }}</div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  label: { type: String, required: true },
  value: { type: [Number, String], default: 0 },
  hint: { type: String, default: '' },
  icon: { type: [Object, Function], default: null },
  tone: { type: String, default: 'indigo' }, // indigo | emerald | rose | amber
});

const tones = {
  indigo: {
    iconWrap: 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20',
    value: 'text-ink',
  },
  emerald: {
    iconWrap: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20',
    value: 'text-emerald-600 dark:text-emerald-400',
  },
  rose: {
    iconWrap: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20',
    value: 'text-rose-600 dark:text-rose-400',
  },
  amber: {
    iconWrap: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20',
    value: 'text-amber-600 dark:text-amber-400',
  },
};

const displayValue = computed(() => {
  if (typeof props.value === 'number') return props.value.toLocaleString();
  return props.value;
});
</script>
