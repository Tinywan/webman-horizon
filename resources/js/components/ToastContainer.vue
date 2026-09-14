<template>
  <Teleport to="body">
    <div class="fixed top-5 right-5 z-[80] flex flex-col gap-2.5 w-72 pointer-events-none">
      <TransitionGroup name="toast">
        <div
          v-for="t in toasts"
          :key="t.id"
          class="pointer-events-auto card px-4 py-3 flex items-center gap-3 shadow-drawer"
          :class="{
            'border-emerald-500/20': t.type === 'success',
            'border-rose-500/20': t.type === 'error',
            'border-amber-500/20': t.type === 'warning',
          }"
        >
          <CheckCircle2 v-if="t.type === 'success'" :size="16" class="text-emerald-600 dark:text-emerald-400 shrink-0" />
          <AlertCircle v-else-if="t.type === 'error'" :size="16" class="text-rose-600 dark:text-rose-400 shrink-0" />
          <AlertTriangle v-else :size="16" class="text-amber-600 dark:text-amber-400 shrink-0" />
          <span class="text-xs text-ink-secondary leading-relaxed">{{ t.message }}</span>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { CheckCircle2, AlertCircle, AlertTriangle } from 'lucide-vue-next';
import { toasts } from '@/composables/useToast';
</script>
