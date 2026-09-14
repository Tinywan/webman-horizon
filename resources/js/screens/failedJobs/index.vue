<template>
  <div class="space-y-6">
    <ErrorAlert v-if="error" :message="error" @retry="loadData" />

    <div class="card overflow-hidden">
      <!-- 头部：标题 + 队列选择 + 操作 -->
      <div class="px-6 py-4 border-b border-line flex flex-wrap gap-3 justify-between items-center">
        <div>
          <h3 class="text-sm font-semibold text-ink">{{ t('failedJobs.title') }}</h3>
          <p class="text-xs text-ink-faint mt-0.5">
            {{ t('failedJobs.totalRecordsPrefix') }}<span class="font-mono text-rose-600 dark:text-rose-400">{{ result.total }}</span>{{ t('failedJobs.totalRecordsSuffix') }}
          </p>
        </div>
        <div class="flex items-center gap-2.5">
          <div class="relative">
            <select
              v-model="queue"
              class="select text-xs"
              @change="onQueueChange"
            >
              <option v-for="q in queueOptions" :key="q" :value="q">{{ q }}</option>
            </select>
            <ChevronDown :size="13" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-ink-faint pointer-events-none" />
          </div>
          <button
            class="btn-primary"
            :disabled="result.total === 0 || acting"
            @click="retryAll"
          >
            <RotateCcw :size="13" />
            {{ t('failedJobs.retryAll') }}
          </button>
        </div>
      </div>

      <!-- 骨架屏 -->
      <Skeleton v-if="loading" variant="rows" :count="5" />

      <!-- 空状态 -->
      <EmptyState
        v-else-if="result.items.length === 0"
        :icon="CheckCircle2"
        :title="t('failedJobs.empty')"
        :description="t('failedJobs.emptyDesc', { queue })"
      />

      <!-- 失败任务表 -->
      <div v-else class="overflow-x-auto">
        <table class="h-table min-w-[720px]">
          <thead>
            <tr>
              <th class="w-10"></th>
              <th>{{ t('failedJobs.thJobClass') }}</th>
              <th>{{ t('failedJobs.thException') }}</th>
              <th>{{ t('failedJobs.thFailedAt') }}</th>
              <th class="text-right">{{ t('failedJobs.thActions') }}</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="job in result.items" :key="`${job.queue}-${job.index}`">
              <tr>
                <td>
                  <button
                    class="btn-icon !p-1"
                    :title="expanded[job.index] ? t('failedJobs.collapse') : t('failedJobs.expand')"
                    @click="toggleExpand(job.index)"
                  >
                    <ChevronRight
                      :size="14"
                      class="transition-transform duration-150"
                      :class="{ 'rotate-90': expanded[job.index] }"
                    />
                  </button>
                </td>
                <td>
                  <div class="font-mono text-xs font-medium text-ink break-all">{{ job.class }}</div>
                  <div class="text-[11px] text-ink-faint font-mono mt-0.5">#{{ job.id }}</div>
                </td>
                <td>
                  <span class="text-xs text-rose-600/90 dark:text-rose-400/90 font-mono line-clamp-2 break-all">
                    {{ firstLine(job.exception) }}
                  </span>
                </td>
                <td class="text-xs text-ink-faint font-mono whitespace-nowrap">
                  {{ formatTime(job.failed_at) }}
                </td>
                <td class="text-right whitespace-nowrap space-x-2">
                  <button
                    class="btn-indigo-soft"
                    :disabled="acting"
                    @click="retryJob(job)"
                  >
                    <RotateCcw :size="12" />
                    {{ t('failedJobs.retry') }}
                  </button>
                  <button
                    class="btn-danger"
                    :disabled="acting"
                    @click="askDelete(job)"
                  >
                    <Trash2 :size="12" />
                    {{ t('failedJobs.delete') }}
                  </button>
                </td>
              </tr>
              <!-- 展开详情 -->
              <tr v-if="expanded[job.index]" class="!bg-surface">
                <td colspan="5" class="!px-6 !py-5">
                  <div class="space-y-4 animate-fade-in-up">
                    <div v-if="job.exception">
                      <div class="text-[11px] uppercase tracking-wider text-ink-faint mb-2">{{ t('failedJobs.exception') }}</div>
                      <div class="code-block text-rose-700/80 dark:text-rose-300/80">{{ job.exception }}</div>
                    </div>
                    <div>
                      <div class="text-[11px] uppercase tracking-wider text-ink-faint mb-2">{{ t('failedJobs.payload') }}</div>
                      <div class="code-block">{{ prettyPayload(job.payload) }}</div>
                    </div>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- 分页 -->
      <div
        v-if="!loading && result.total > 0"
        class="px-6 py-3.5 border-t border-line flex items-center justify-between"
      >
        <span class="text-xs text-ink-faint font-mono">
          {{ t('failedJobs.pagination', { page: result.page, pages: totalPages, total: result.total }) }}
        </span>
        <div class="flex items-center gap-2">
          <button
            class="btn-ghost"
            :disabled="result.page <= 1"
            @click="gotoPage(result.page - 1)"
          >
            <ChevronLeft :size="13" />
            {{ t('failedJobs.prevPage') }}
          </button>
          <button
            class="btn-ghost"
            :disabled="result.page >= totalPages"
            @click="gotoPage(result.page + 1)"
          >
            {{ t('failedJobs.nextPage') }}
            <ChevronRight :size="13" />
          </button>
        </div>
      </div>
    </div>

    <!-- 删除二次确认 -->
    <ConfirmDialog
      :open="!!pendingDelete"
      :title="t('failedJobs.deleteTitle')"
      :message="t('failedJobs.deleteMessage', { name: pendingDelete?.class || '' })"
      :confirm-text="t('failedJobs.confirmDelete')"
      danger
      :loading="acting"
      @confirm="confirmDelete"
      @cancel="pendingDelete = null"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import {
  RotateCcw,
  Trash2,
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  CheckCircle2,
} from 'lucide-vue-next';
import api from '@/lib/api';
import { usePolling } from '@/composables/usePolling';
import { useI18n, locale } from '@/composables/useI18n';
import { toast } from '@/composables/useToast';
import Skeleton from '@/components/Skeleton.vue';
import EmptyState from '@/components/EmptyState.vue';
import ErrorAlert from '@/components/ErrorAlert.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';

const { t } = useI18n();

const PAGE_SIZE = 15;

const queue = ref('default');
const queueOptions = ref(['default']);
const result = ref({ total: 0, page: 1, page_size: PAGE_SIZE, items: [] });
const loading = ref(true);
const error = ref('');
const acting = ref(false);
const expanded = ref({});
const pendingDelete = ref(null);

const totalPages = computed(() =>
  Math.max(1, Math.ceil(result.value.total / result.value.page_size))
);

const loadData = async () => {
  try {
    const [queues, data] = await Promise.all([
      api.getQueues().catch(() => []),
      api.getFailedJobs(queue.value, result.value.page, PAGE_SIZE),
    ]);
    if (queues && queues.length) {
      const names = queues.map((q) => q.queue);
      queueOptions.value = names.includes(queue.value) ? names : [...names, queue.value];
    }
    result.value = data || { total: 0, page: 1, page_size: PAGE_SIZE, items: [] };
    error.value = '';
  } catch (e) {
    error.value = e.message || t('common.loadFailed');
  } finally {
    loading.value = false;
  }
};

usePolling(loadData);

const onQueueChange = () => {
  result.value.page = 1;
  expanded.value = {};
  loadData();
};

const gotoPage = (page) => {
  result.value.page = page;
  expanded.value = {};
  loadData();
};

const toggleExpand = (index) => {
  expanded.value[index] = !expanded.value[index];
};

const firstLine = (exception) => {
  if (!exception) return t('failedJobs.noStack');
  return String(exception).split('\n')[0];
};

const prettyPayload = (payload) => {
  try {
    return JSON.stringify(payload, null, 2);
  } catch (e) {
    return String(payload);
  }
};

const formatTime = (ts) => {
  if (!ts) return '—';
  // 兼容秒/毫秒时间戳与日期字符串
  if (typeof ts === 'number') {
    const ms = ts < 1e12 ? ts * 1000 : ts;
    return new Date(ms).toLocaleString(locale.value, { hour12: false });
  }
  return String(ts);
};

const retryJob = async (job) => {
  acting.value = true;
  try {
    await api.retryJob(job.queue, job.index);
    toast(t('failedJobs.retried', { name: job.class }));
    await loadData();
  } catch (e) {
    toast(e.message || t('failedJobs.retryFailed'), 'error');
  } finally {
    acting.value = false;
  }
};

const retryAll = async () => {
  acting.value = true;
  try {
    const res = await api.retryAllJobs(queue.value);
    const count = res?.retried_count ?? 0;
    toast(
      count > 0 ? t('failedJobs.retriedCount', { count }) : t('failedJobs.nothingToRetry'),
      count > 0 ? 'success' : 'warning'
    );
    result.value.page = 1;
    await loadData();
  } catch (e) {
    toast(e.message || t('failedJobs.retryAllFailed'), 'error');
  } finally {
    acting.value = false;
  }
};

const askDelete = (job) => {
  pendingDelete.value = job;
};

const confirmDelete = async () => {
  if (!pendingDelete.value) return;
  acting.value = true;
  try {
    await api.deleteJob(pendingDelete.value.queue, pendingDelete.value.index);
    toast(t('failedJobs.deleted'));
    pendingDelete.value = null;
    await loadData();
  } catch (e) {
    toast(e.message || t('failedJobs.deleteFailed'), 'error');
  } finally {
    acting.value = false;
  }
};
</script>
