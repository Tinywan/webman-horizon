<template>
  <div class="space-y-6">
    <ErrorAlert v-if="error" :message="error" @retry="loadData" />

    <!-- 队列选择器 -->
    <div class="flex flex-wrap items-center gap-2">
      <span class="text-xs text-ink-faint mr-1">{{ t('metrics.selectQueue') }}</span>
      <button
        v-for="q in queues"
        :key="q.queue"
        class="px-3.5 py-1.5 rounded-lg text-xs font-mono border transition-colors duration-150"
        :class="selectedQueue === q.queue
          ? 'bg-indigo-600 border-indigo-600 text-white'
          : 'border-line-strong text-ink-muted hover:text-ink hover:bg-surface-strong'"
        @click="selectQueue(q.queue)"
      >
        {{ q.queue }}
      </button>
      <span v-if="loading" class="text-xs text-ink-faint animate-pulse">{{ t('metrics.loadingQueues') }}</span>
    </div>

    <!-- 选中队列指标卡 -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
      <StatCard
        :label="t('metrics.avgRuntime')"
        :value="`${selectedMetrics.avg_runtime_ms ?? 0} ms`"
        :hint="t('metrics.avgRuntimeHint')"
        :icon="Timer"
        tone="emerald"
      />
      <StatCard
        :label="t('metrics.waitingJobs')"
        :value="selectedQueueInfo.waiting ?? 0"
        :hint="t('metrics.waitingJobsHint')"
        :icon="HourglassIcon"
        tone="indigo"
      />
      <StatCard
        :label="t('metrics.failedJobs')"
        :value="selectedQueueInfo.failed ?? 0"
        :hint="t('metrics.failedJobsHint')"
        :icon="XCircle"
        tone="rose"
      />
    </div>

    <!-- 选中队列吞吐图 -->
    <div class="card p-6">
      <div class="flex items-center justify-between mb-2">
        <div>
          <h3 class="text-sm font-semibold text-ink">
            {{ t('dashboard.throughput') }} · <span class="font-mono text-indigo-600 dark:text-indigo-400">{{ selectedQueue }}</span>
          </h3>
          <p class="text-xs text-ink-faint mt-1">{{ t('metrics.throughputDesc') }}</p>
        </div>
      </div>
      <EmptyState
        v-if="throughputEmpty"
        :icon="LineChart"
        :title="t('metrics.noThroughput')"
        :description="t('metrics.noThroughputDesc')"
      />
      <VChart v-else :option="chartOption" height="16rem" />
    </div>

    <!-- 全部队列耗时表 -->
    <Skeleton v-if="loading" variant="rows" :count="3" />
    <div v-else class="card overflow-hidden">
      <div class="px-6 py-4 border-b border-line">
        <h3 class="text-sm font-semibold text-ink">{{ t('metrics.runtimeTitle') }}</h3>
        <p class="text-xs text-ink-faint mt-0.5">{{ t('metrics.runtimeDesc') }}</p>
      </div>

      <EmptyState
        v-if="queues.length === 0"
        :icon="Gauge"
        :title="t('metrics.noMetrics')"
        :description="t('metrics.noMetricsDesc')"
      />

      <div v-else class="overflow-x-auto">
        <table class="h-table min-w-[640px]">
          <thead>
            <tr>
              <th>{{ t('metrics.thQueue') }}</th>
              <th>{{ t('metrics.thAvgRuntime') }}</th>
              <th>{{ t('metrics.thWaiting') }}</th>
              <th>{{ t('metrics.thDelayed') }}</th>
              <th>{{ t('metrics.thFailed') }}</th>
              <th class="text-right">{{ t('metrics.thHealth') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="q in queues"
              :key="q.queue"
              class="cursor-pointer"
              @click="selectQueue(q.queue)"
            >
              <td class="font-mono font-medium text-ink">{{ q.queue }}</td>
              <td class="font-mono text-emerald-600 dark:text-emerald-400">{{ runtimes[q.queue] ?? '—' }} ms</td>
              <td><Badge tone="indigo">{{ q.waiting }}</Badge></td>
              <td><Badge tone="amber">{{ q.delayed }}</Badge></td>
              <td><Badge :tone="q.failed > 0 ? 'rose' : 'gray'">{{ q.failed }}</Badge></td>
              <td class="text-right">
                <span
                  class="inline-flex items-center gap-1.5 text-xs"
                  :class="q.failed > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400'"
                >
                  <span
                    class="w-1.5 h-1.5 rounded-full"
                    :class="q.failed > 0 ? 'bg-amber-400' : 'bg-emerald-400'"
                  ></span>
                  {{ q.failed > 0 ? t('metrics.degraded') : t('metrics.healthy') }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Timer, Hourglass as HourglassIcon, XCircle, Gauge, LineChart } from 'lucide-vue-next';
import api from '@/lib/api';
import { usePolling } from '@/composables/usePolling';
import { useI18n } from '@/composables/useI18n';
import { chartTheme, baseGrid, areaSeries } from '@/lib/chartTheme';
import StatCard from '@/components/StatCard.vue';
import Badge from '@/components/Badge.vue';
import Skeleton from '@/components/Skeleton.vue';
import EmptyState from '@/components/EmptyState.vue';
import ErrorAlert from '@/components/ErrorAlert.vue';
import VChart from '@/components/VChart.vue';

const { t } = useI18n();

const queues = ref([]);
const runtimes = ref({});
const selectedQueue = ref('default');
const selectedMetrics = ref({});
const throughput = ref([]);
const loading = ref(true);
const error = ref('');

const loadData = async () => {
  try {
    const list = (await api.getQueues()) || [];
    queues.value = list;

    // 若当前选中队列不存在则回退到第一个
    if (!list.some((q) => q.queue === selectedQueue.value) && list.length) {
      selectedQueue.value = list[0].queue;
    }

    const [metrics, history, ...runtimeList] = await Promise.all([
      api.getMetrics(selectedQueue.value),
      api.getThroughput(selectedQueue.value, 60),
      ...list.map((q) => api.getMetrics(q.queue).catch(() => null)),
    ]);

    selectedMetrics.value = metrics || {};
    throughput.value = history || [];

    const map = {};
    list.forEach((q, i) => {
      map[q.queue] = runtimeList[i] ? runtimeList[i].avg_runtime_ms : null;
    });
    runtimes.value = map;
    error.value = '';
  } catch (e) {
    error.value = e.message || t('common.loadFailed');
  } finally {
    loading.value = false;
  }
};

usePolling(loadData);

const selectQueue = (queue) => {
  if (queue === selectedQueue.value) return;
  selectedQueue.value = queue;
  loadData();
};

const selectedQueueInfo = computed(
  () => queues.value.find((q) => q.queue === selectedQueue.value) || {}
);

const throughputEmpty = computed(
  () => throughput.value.reduce((sum, item) => sum + (item.count || 0), 0) === 0
);

const chartOption = computed(() => ({
  backgroundColor: 'transparent',
  tooltip: { ...chartTheme.tooltip, valueFormatter: (v) => t('metrics.jobsUnit', { count: v }) },
  grid: baseGrid,
  xAxis: { ...chartTheme.xAxis, data: throughput.value.map((d) => d.time) },
  yAxis: { ...chartTheme.yAxis, minInterval: 1 },
  series: [areaSeries(t('metrics.completedJobs'), throughput.value.map((d) => d.count), '#34d399')],
}));
</script>
