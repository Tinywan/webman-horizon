<template>
  <div class="flex flex-col h-full">
    <!-- 品牌区 -->
    <div class="h-16 flex items-center gap-3 px-5 border-b border-line">
      <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center shadow-glow-primary">
        <Telescope :size="18" class="text-white" :stroke-width="2" />
      </div>
      <div class="leading-tight">
        <div class="text-sm font-bold text-ink tracking-tight">Webman Horizon</div>
        <div class="text-[10px] text-ink-faint uppercase tracking-widest">{{ t('brand.subtitle') }}</div>
      </div>
    </div>

    <!-- 导航 -->
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
      <router-link
        v-for="item in navs"
        :key="item.path"
        :to="item.path"
        custom
        v-slot="{ navigate, isActive }"
      >
        <a
          @click="go(navigate)"
          class="relative flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors duration-150 cursor-pointer group"
          :class="isActive
            ? 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-300 font-medium'
            : 'text-ink-muted hover:text-ink hover:bg-surface-strong'"
        >
          <span
            class="absolute left-0 top-1/2 -translate-y-1/2 w-0.5 h-5 rounded-full bg-indigo-500 transition-opacity duration-150"
            :class="isActive ? 'opacity-100' : 'opacity-0'"
          ></span>
          <component
            :is="item.icon"
            :size="17"
            :stroke-width="isActive ? 2.2 : 1.8"
            :class="isActive ? 'text-indigo-500 dark:text-indigo-400' : 'text-ink-faint group-hover:text-ink-muted'"
          />
          <span>{{ t(item.nameKey) }}</span>
        </a>
      </router-link>
    </nav>

    <!-- 底部状态 -->
    <div class="px-5 py-4 border-t border-line space-y-2">
      <div class="flex items-center justify-between text-[11px]">
        <span class="text-ink-faint">{{ t('brand.driver') }}</span>
        <span class="font-mono text-ink-muted">redis-queue</span>
      </div>
      <div class="flex items-center justify-between text-[11px]">
        <span class="text-ink-faint">{{ t('brand.version') }}</span>
        <span class="font-mono text-ink-muted">v1.0.0</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  LayoutDashboard,
  Activity,
  Gauge,
  History,
  XCircle,
  Layers,
  Telescope,
} from 'lucide-vue-next';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

const emit = defineEmits(['navigate']);

const navs = [
  { nameKey: 'nav.dashboard', path: '/dashboard', icon: LayoutDashboard },
  { nameKey: 'nav.monitoring', path: '/monitoring', icon: Activity },
  { nameKey: 'nav.metrics', path: '/metrics', icon: Gauge },
  { nameKey: 'nav.recentJobs', path: '/recent-jobs', icon: History },
  { nameKey: 'nav.failedJobs', path: '/failed-jobs', icon: XCircle },
  { nameKey: 'nav.batches', path: '/batches', icon: Layers },
];

const go = (navigate) => {
  navigate();
  emit('navigate');
};
</script>
