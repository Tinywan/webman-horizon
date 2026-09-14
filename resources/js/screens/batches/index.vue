<template>
  <div class="card">
    <EmptyState
      :icon="Layers"
      :title="t('batches.unavailable')"
      :description="t('batches.unavailableDesc')"
    >
      <div class="flex flex-wrap items-center justify-center gap-2.5">
        <router-link to="/dashboard" class="btn-indigo-soft">
          <LayoutDashboard :size="13" />
          {{ t('batches.backDashboard') }}
        </router-link>
        <router-link to="/monitoring" class="btn-ghost">
          <Activity :size="13" />
          {{ t('batches.gotoMonitoring') }}
        </router-link>
      </div>
    </EmptyState>
  </div>
</template>

<script setup>
import { Layers, LayoutDashboard, Activity } from 'lucide-vue-next';
import EmptyState from '@/components/EmptyState.vue';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();
</script>
