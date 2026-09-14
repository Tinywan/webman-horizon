import { reactive, computed, onMounted, onBeforeUnmount } from 'vue';

/**
 * 全局共享轮询状态
 * - interval: 轮询间隔（ms），0 表示手动暂停
 * - hidden: 页面不可见时由 visibilitychange 自动置为 true（自动暂停）
 */
export const INTERVAL_OPTIONS = [
  { label: '3s', value: 3000 },
  { label: '5s', value: 5000 },
  { label: '10s', value: 10000 },
];

export const polling = reactive({
  interval: 5000,
  hidden: document.hidden,
});

/** 是否处于暂停（手动暂停或页面隐藏自动暂停） */
export const isPaused = computed(() => polling.interval === 0 || polling.hidden);

const fetchers = new Set();
let timer = null;

const clearTimer = () => {
  if (timer) {
    clearInterval(timer);
    timer = null;
  }
};

const runAll = () => {
  fetchers.forEach((fn) => {
    try {
      fn();
    } catch (e) {
      // 单个页面轮询失败不影响其他页面
    }
  });
};

const restartTimer = () => {
  clearTimer();
  if (polling.interval > 0 && !polling.hidden && fetchers.size > 0) {
    timer = setInterval(runAll, polling.interval);
  }
};

/** 切换全局轮询间隔（0 = 暂停） */
export const setPollingInterval = (ms) => {
  polling.interval = ms;
  restartTimer();
};

/** 顶栏手动刷新：立即触发所有已注册的轮询回调 */
export const refreshNow = () => {
  runAll();
};

document.addEventListener('visibilitychange', () => {
  polling.hidden = document.hidden;
  restartTimer();
});

/**
 * 注册一个随全局轮询驱动的数据加载函数
 * @param {Function} fetchFn 数据加载函数（可异步，内部需自行捕获错误）
 * @param {Object} options immediate: 挂载后立即执行一次
 */
export function usePolling(fetchFn, { immediate = true } = {}) {
  onMounted(() => {
    fetchers.add(fetchFn);
    if (immediate) fetchFn();
    restartTimer();
  });

  onBeforeUnmount(() => {
    fetchers.delete(fetchFn);
    if (fetchers.size === 0) clearTimer();
  });
}
