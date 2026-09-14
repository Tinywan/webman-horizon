<template>
  <div class="card">
    <EmptyState
      :icon="History"
      :title="t('recentJobs.unavailable')"
      :description="t('recentJobs.unavailableDesc')"
    >
      <div class="flex flex-wrap items-center justify-center gap-2.5">
        <router-link to="/dashboard" class="btn-indigo-soft">
          <LayoutDashboard :size="13" />
          {{ t('recentJobs.viewThroughput') }}
        </router-link>
        <router-link to="/failed-jobs" class="btn-ghost">
          <XCircle :size="13" />
          {{ t('recentJobs.viewFailed') }}
        </router-link>
      </div>
    </EmptyState>
  </div>
</template>

<script setup>
import { History, LayoutDashboard, XCircle } from 'lucide-vue-next';
import EmptyState from '@/components/EmptyState.vue';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();
</script>
