<template>
  <div class="space-y-6">
    <!-- 错误提示 -->
    <ErrorAlert v-if="error" :message="error" @retry="loadData" />

    <!-- 概览指标卡片 -->
    <Skeleton v-if="loading" variant="cards" :count="4" />
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 animate-fade-in-up">
      <StatCard
        :label="t('dashboard.jobsPerMinute')"
        :value="jobsPerMinute"
        :hint="t('dashboard.jobsPerMinuteHint')"
        :icon="Zap"
        tone="indigo"
      />
      <StatCard
        :label="t('dashboard.jobsPastHour')"
        :value="jobsPastHour"
        :hint="t('dashboard.jobsPastHourHint')"
        :icon="TrendingUp"
        tone="emerald"
      />
      <StatCard
        :label="t('dashboard.failedJobs')"
        :value="stats.total_failed ?? 0"
        :hint="t('dashboard.failedJobsHint')"
        :icon="XCircle"
        tone="rose"
      />
      <StatCard
        :label="t('dashboard.totalQueues')"
        :value="stats.queue_count ?? 0"
        :hint="t('dashboard.totalQueuesHint', { waiting: stats.total_waiting ?? 0, delayed: stats.total_delayed ?? 0 })"
        :icon="Layers"
        tone="amber"
      />
    </div>

    <!-- 吞吐量走势图 -->
    <Skeleton v-if="loading" variant="chart" />
    <div v-else class="card p-6">
      <div class="flex items-center justify-between mb-2">
        <div>
          <h3 class="text-sm font-semibold text-ink">{{ t('dashboard.throughput') }}</h3>
          <p class="text-xs text-ink-faint mt-1">{{ t('dashboard.throughputDesc') }}</p>
        </div>
        <Badge tone="indigo">{{ t('dashboard.perHour', { count: jobsPastHour }) }}</Badge>
      </div>
      <EmptyState
        v-if="throughputEmpty"
        :icon="LineChart"
        :title="t('dashboard.noThroughput')"
        :description="t('dashboard.noThroughputDesc')"
      />
      <VChart v-else :option="chartOption" height="18rem" />
    </div>

    <!-- 队列负载概览 -->
    <Skeleton v-if="loading" variant="rows" :count="4" />
    <div v-else class="card overflow-hidden">
      <div class="px-6 py-4 border-b border-line flex justify-between items-center">
        <div>
          <h3 class="text-sm font-semibold text-ink">{{ t('dashboard.workload') }}</h3>
          <p class="text-xs text-ink-faint mt-0.5">{{ t('dashboard.workloadDesc') }}</p>
        </div>
        <span class="text-xs text-ink-faint font-mono">{{ t('common.queueCount', { count: queues.length }) }}</span>
      </div>

      <EmptyState
        v-if="queues.length === 0"
        :icon="Inbox"
        :title="t('dashboard.noQueues')"
        :description="t('dashboard.noQueuesDesc')"
      />

      <div v-else class="overflow-x-auto">
        <table class="h-table min-w-[640px]">
          <thead>
            <tr>
              <th>{{ t('dashboard.thQueue') }}</th>
              <th>{{ t('dashboard.thWaiting') }}</th>
              <th>{{ t('dashboard.thDelayed') }}</th>
              <th>{{ t('dashboard.thFailed') }}</th>
              <th class="text-right">{{ t('dashboard.thStatus') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="q in queues" :key="q.queue">
              <td class="font-mono font-medium text-ink">{{ q.queue }}</td>
              <td><Badge tone="indigo">{{ q.waiting }}</Badge></td>
              <td><Badge tone="amber">{{ q.delayed }}</Badge></td>
              <td><Badge :tone="q.failed > 0 ? 'rose' : 'gray'">{{ q.failed }}</Badge></td>
              <td class="text-right">
                <span class="inline-flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                  {{ t('dashboard.running') }}
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
import { Zap, TrendingUp, XCircle, Layers, Inbox, LineChart } from 'lucide-vue-next';
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

const stats = ref({});
const queues = ref([]);
const throughput = ref([]);
const loading = ref(true);
const error = ref('');

const loadData = async () => {
  try {
    const [s, q, th] = await Promise.all([
      api.getStats(),
      api.getQueues(),
      api.getThroughput('total', 60),
    ]);
    stats.value = s || {};
    queues.value = q || [];
    throughput.value = th || [];
    error.value = '';
  } catch (e) {
    error.value = e.message || t('common.loadFailed');
  } finally {
    loading.value = false;
  }
};

usePolling(loadData);

const jobsPerMinute = computed(() => {
  const list = throughput.value;
  return list.length ? list[list.length - 1].count : 0;
});

const jobsPastHour = computed(() =>
  throughput.value.reduce((sum, item) => sum + (item.count || 0), 0)
);

const throughputEmpty = computed(() => jobsPastHour.value === 0);

const chartOption = computed(() => ({
  backgroundColor: 'transparent',
  tooltip: { ...chartTheme.tooltip, valueFormatter: (v) => t('dashboard.jobsUnit', { count: v }) },
  grid: baseGrid,
  xAxis: { ...chartTheme.xAxis, data: throughput.value.map((d) => d.time) },
  yAxis: { ...chartTheme.yAxis, minInterval: 1 },
  series: [areaSeries(t('dashboard.completedJobs'), throughput.value.map((d) => d.count))],
}));
</script>
