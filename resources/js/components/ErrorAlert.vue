<template>
  <div class="card border-rose-500/20 bg-rose-500/[0.04] px-5 py-4 flex items-center gap-4">
    <div class="w-8 h-8 rounded-lg bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-600 dark:text-rose-400 shrink-0">
      <AlertCircle :size="16" />
    </div>
    <div class="flex-1 min-w-0">
      <div class="text-sm font-medium text-rose-700 dark:text-rose-300">{{ t('error.title') }}</div>
      <div class="text-xs text-rose-600/70 dark:text-rose-400/70 mt-0.5 truncate">{{ message }}</div>
    </div>
    <button class="btn-danger shrink-0" @click="$emit('retry')">
      <RotateCcw :size="13" />
      {{ t('common.retry') }}
    </button>
  </div>
</template>

<script setup>
import { AlertCircle, RotateCcw } from 'lucide-vue-next';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

defineProps({
  message: { type: String, default: '' },
});
defineEmits(['retry']);
</script>
