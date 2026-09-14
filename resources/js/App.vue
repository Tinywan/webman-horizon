<template>
  <div class="min-h-screen bg-base">
    <!-- 桌面端固定侧边栏 -->
    <aside class="hidden lg:block fixed inset-y-0 left-0 w-60 border-r border-line bg-card/30 z-40">
      <SideNav />
    </aside>

    <!-- 移动端抽屉 -->
    <Transition name="fade">
      <div
        v-if="drawerOpen"
        class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm lg:hidden"
        @click="drawerOpen = false"
      ></div>
    </Transition>
    <Transition name="drawer">
      <aside
        v-if="drawerOpen"
        class="fixed inset-y-0 left-0 w-64 z-[60] bg-card border-r border-line-strong shadow-drawer lg:hidden"
      >
        <button
          class="absolute top-4 right-4 btn-icon"
          :title="t('header.closeMenu')"
          @click="drawerOpen = false"
        >
          <X :size="18" />
        </button>
        <SideNav @navigate="drawerOpen = false" />
      </aside>
    </Transition>

    <!-- 主区域 -->
    <div class="lg:pl-60 flex flex-col min-h-screen">
      <!-- 顶部栏 -->
      <header class="sticky top-0 z-30 border-b border-line bg-base/85 backdrop-blur-md">
        <div class="h-16 px-4 sm:px-6 lg:px-8 flex items-center gap-2.5 sm:gap-4">
          <!-- 汉堡按钮（移动端） -->
          <button class="btn-icon lg:hidden" :title="t('header.openMenu')" @click="drawerOpen = true">
            <Menu :size="18" />
          </button>

          <!-- 当前页面标题 -->
          <div class="min-w-0 flex-1">
            <h1 class="text-sm font-semibold text-ink truncate">
              {{ route.meta.titleKey ? t(route.meta.titleKey) : t('nav.dashboard') }}
            </h1>
            <p class="text-xs text-ink-faint truncate hidden sm:block">
              {{ route.meta.subtitleKey ? t(route.meta.subtitleKey) : '' }}
            </p>
          </div>

          <!-- 系统状态脉冲 -->
          <div class="hidden md:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
            <span class="relative flex w-1.5 h-1.5">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-60"></span>
              <span class="relative inline-flex rounded-full w-1.5 h-1.5 bg-emerald-400"></span>
            </span>
            {{ t('header.active') }}
          </div>

          <!-- 轮询间隔控制 -->
          <div class="hidden sm:flex items-center rounded-lg border border-line-strong bg-surface overflow-hidden">
            <button
              v-for="opt in INTERVAL_OPTIONS"
              :key="opt.value"
              class="px-2.5 sm:px-3 py-1.5 text-[11px] font-mono transition-colors duration-150"
              :class="polling.interval === opt.value
                ? 'bg-indigo-600 text-white'
                : 'text-ink-muted hover:text-ink hover:bg-surface-strong'"
              :title="t('header.autoRefresh', { label: opt.label })"
              @click="setPollingInterval(opt.value)"
            >
              {{ opt.label }}
            </button>
            <button
              class="px-2.5 sm:px-3 py-1.5 text-[11px] transition-colors duration-150 border-l border-line-strong"
              :class="polling.interval === 0
                ? 'bg-amber-500/20 text-amber-600 dark:text-amber-400'
                : 'text-ink-muted hover:text-ink hover:bg-surface-strong'"
              :title="t('header.pauseRefresh')"
              @click="setPollingInterval(0)"
            >
              <Pause :size="12" />
            </button>
          </div>

          <!-- 暂停状态提示 -->
          <div
            v-if="isPaused"
            class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20"
          >
            <Pause :size="11" />
            {{ polling.hidden ? t('header.hiddenPaused') : t('header.paused') }}
          </div>

          <!-- 手动刷新 -->
          <button class="btn-icon border border-line-strong" :title="t('header.refresh')" @click="manualRefresh">
            <RefreshCw :size="15" :class="{ 'animate-spin': spinning }" />
          </button>

          <!-- 语言切换 -->
          <button
            class="btn-icon border border-line-strong"
            :title="t('header.switchLanguage')"
            @click="toggleLocale"
          >
            <Languages :size="15" />
          </button>

          <!-- 主题切换（light → dark → system 循环） -->
          <button
            class="btn-icon border border-line-strong"
            :title="t('header.theme', { mode: themeModeLabel })"
            @click="cycleTheme"
          >
            <Sun v-if="themeMode === 'light'" :size="15" />
            <Moon v-else-if="themeMode === 'dark'" :size="15" />
            <Monitor v-else :size="15" />
          </button>
        </div>
      </header>

      <!-- 页面内容 -->
      <main class="flex-1 px-4 sm:px-6 lg:px-8 py-8">
        <div class="max-w-7xl mx-auto">
          <router-view />
        </div>
      </main>
    </div>

    <ToastContainer />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRoute } from 'vue-router';
import { Menu, X, RefreshCw, Pause, Languages, Sun, Moon, Monitor } from 'lucide-vue-next';
import SideNav from '@/components/SideNav.vue';
import ToastContainer from '@/components/ToastContainer.vue';
import {
  polling,
  isPaused,
  INTERVAL_OPTIONS,
  setPollingInterval,
  refreshNow,
} from '@/composables/usePolling';
import { useI18n } from '@/composables/useI18n';
import { useTheme } from '@/composables/useTheme';

const { t, toggleLocale } = useI18n();
const { themeMode, cycleTheme } = useTheme();

const themeModeLabel = computed(() => {
  const map = {
    light: t('header.themeLight'),
    dark: t('header.themeDark'),
    system: t('header.themeSystem'),
  };
  return map[themeMode.value];
});

const route = useRoute();
const drawerOpen = ref(false);
const spinning = ref(false);

const manualRefresh = () => {
  spinning.value = true;
  refreshNow();
  setTimeout(() => {
    spinning.value = false;
  }, 600);
};
</script>
