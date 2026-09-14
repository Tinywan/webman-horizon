<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="open"
        class="fixed inset-0 z-[70] bg-black/60 backdrop-blur-sm flex items-center justify-center p-4"
        @click.self="$emit('cancel')"
      >
        <Transition name="modal" appear>
          <div class="card w-full max-w-sm p-6 shadow-drawer">
            <div class="flex items-start gap-4">
              <div
                class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                :class="danger
                  ? 'bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400'
                  : 'bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400'"
              >
                <component :is="danger ? AlertTriangle : HelpCircle" :size="18" />
              </div>
              <div class="min-w-0">
                <h3 class="text-sm font-semibold text-ink">{{ title || t('common.confirmAction') }}</h3>
                <p class="mt-1.5 text-xs text-ink-muted leading-relaxed">{{ message }}</p>
              </div>
            </div>
            <div class="mt-6 flex justify-end gap-2.5">
              <button class="btn-ghost" @click="$emit('cancel')">{{ t('common.cancel') }}</button>
              <button
                :class="danger ? 'btn-danger' : 'btn-primary'"
                :disabled="loading"
                @click="$emit('confirm')"
              >
                {{ loading ? t('common.processing') : (confirmText || t('common.confirm')) }}
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { AlertTriangle, HelpCircle } from 'lucide-vue-next';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: '' },
  message: { type: String, default: '' },
  confirmText: { type: String, default: '' },
  danger: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
});
defineEmits(['confirm', 'cancel']);
</script>
