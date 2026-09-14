<template>
  <div class="space-y-6">
    <ErrorAlert v-if="error" :message="error" @retry="loadData" />

    <!-- 标签监控 -->
    <div class="card p-6">
      <div class="flex items-start gap-4">
        <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0">
          <Tag :size="18" />
        </div>
        <div class="flex-1 min-w-0">
          <h3 class="text-sm font-semibold text-ink">{{ t('monitoring.tagsTitle') }}</h3>
          <p class="text-xs text-ink-faint mt-1">
            {{ t('monitoring.tagsDesc') }}
          </p>
          <div class="mt-4 flex flex-col sm:flex-row gap-3">
            <input
              v-model="newTag"
              class="input flex-1 max-w-md"
              :placeholder="t('monitoring.tagPlaceholder')"
              @keyup.enter="addTag"
            />
            <button class="btn-primary" @click="addTag">
              <Plus :size="14" />
              {{ t('monitoring.addTag') }}
            </button>
          </div>
        </div>
      </div>

      <div class="mt-5 pt-5 border-t border-line">
        <div v-if="tags.length === 0" class="text-xs text-ink-faint py-2">
          {{ t('monitoring.noTags') }}
        </div>
        <div v-else class="flex flex-wrap gap-2">
          <span
            v-for="tag in tags"
            :key="tag"
            class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-500/10 text-indigo-600 dark:text-indigo-300 border border-indigo-500/20 rounded-full text-xs font-mono"
          >
            {{ tag }}
            <button
              class="text-ink-faint hover:text-rose-500 dark:hover:text-rose-400 transition-colors"
              :title="t('monitoring.removeTag', { tag })"
              @click="removeTag(tag)"
            >
              <X :size="12" />
            </button>
          </span>
        </div>
      </div>
    </div>

    <!-- 实时队列快照 -->
    <div class="card overflow-hidden">
      <div class="px-6 py-4 border-b border-line flex justify-between items-center">
        <div>
          <h3 class="text-sm font-semibold text-ink">{{ t('monitoring.snapshot') }}</h3>
          <p class="text-xs text-ink-faint mt-0.5">{{ t('monitoring.snapshotDesc') }}</p>
        </div>
        <span class="text-xs text-ink-faint font-mono">{{ t('common.queueCount', { count: queues.length }) }}</span>
      </div>

      <Skeleton v-if="loading" variant="rows" :count="3" />
      <EmptyState
        v-else-if="queues.length === 0"
        :icon="Radar"
        :title="t('monitoring.noActivity')"
        :description="t('monitoring.noActivityDesc')"
      />

      <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 p-6">
        <div
          v-for="q in queues"
          :key="q.queue"
          class="rounded-xl border border-line bg-surface p-4 hover:border-line-strong transition-colors"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 min-w-0">
              <Server :size="14" class="text-ink-faint shrink-0" />
              <span class="font-mono text-sm font-medium text-ink truncate">{{ q.queue }}</span>
            </div>
            <span class="inline-flex items-center gap-1.5 text-[11px] text-emerald-600 dark:text-emerald-400 shrink-0">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
              {{ t('monitoring.live') }}
            </span>
          </div>
          <div class="mt-4 grid grid-cols-3 gap-2">
            <div class="rounded-lg bg-well px-3 py-2.5 text-center">
              <div class="text-lg font-bold font-mono text-indigo-600 dark:text-indigo-400">{{ q.waiting }}</div>
              <div class="text-[10px] text-ink-faint uppercase tracking-wider mt-0.5">{{ t('monitoring.waiting') }}</div>
            </div>
            <div class="rounded-lg bg-well px-3 py-2.5 text-center">
              <div class="text-lg font-bold font-mono text-amber-600 dark:text-amber-400">{{ q.delayed }}</div>
              <div class="text-[10px] text-ink-faint uppercase tracking-wider mt-0.5">{{ t('monitoring.delayed') }}</div>
            </div>
            <div class="rounded-lg bg-well px-3 py-2.5 text-center">
              <div class="text-lg font-bold font-mono" :class="q.failed > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-ink-faint'">
                {{ q.failed }}
              </div>
              <div class="text-[10px] text-ink-faint uppercase tracking-wider mt-0.5">{{ t('monitoring.failed') }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Tag, Plus, X, Server, Radar } from 'lucide-vue-next';
import api from '@/lib/api';
import { usePolling } from '@/composables/usePolling';
import { useI18n } from '@/composables/useI18n';
import { toast } from '@/composables/useToast';
import Skeleton from '@/components/Skeleton.vue';
import EmptyState from '@/components/EmptyState.vue';
import ErrorAlert from '@/components/ErrorAlert.vue';

const { t } = useI18n();

const STORAGE_KEY = 'horizon:monitoring-tags';

const queues = ref([]);
const loading = ref(true);
const error = ref('');

const loadData = async () => {
  try {
    queues.value = (await api.getQueues()) || [];
    error.value = '';
  } catch (e) {
    error.value = e.message || t('common.loadFailed');
  } finally {
    loading.value = false;
  }
};

usePolling(loadData);

// 标签（本地持久化）
const tags = ref([]);
try {
  tags.value = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
} catch (e) {
  tags.value = [];
}

const newTag = ref('');

const persist = () => {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(tags.value));
  } catch (e) {}
};

const addTag = () => {
  const value = newTag.value.trim();
  if (!value) return;
  if (tags.value.includes(value)) {
    toast(t('monitoring.tagExists', { tag: value }), 'warning');
    return;
  }
  tags.value.push(value);
  newTag.value = '';
  persist();
  toast(t('monitoring.tagAdded', { tag: value }));
};

const removeTag = (tag) => {
  tags.value = tags.value.filter((item) => item !== tag);
  persist();
};
</script>
