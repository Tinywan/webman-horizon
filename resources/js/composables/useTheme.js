import { ref, computed, watch } from 'vue';

/**
 * 明暗主题切换
 * - 三种模式：light / dark / system（默认 system 跟随系统）
 * - 选择持久化 localStorage（key: horizon-theme）
 * - system 模式监听 prefers-color-scheme 变化实时切换
 * - 通过在 <html> 上加/移除 dark class 生效（index.html 内联脚本负责首屏防 FOUC）
 */
const STORAGE_KEY = 'horizon-theme';
const MODES = ['light', 'dark', 'system'];

const initMode = () => {
  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored && MODES.includes(stored)) return stored;
  } catch (e) {}
  return 'system';
};

export const themeMode = ref(initMode());

const media = window.matchMedia('(prefers-color-scheme: dark)');
const systemDark = ref(media.matches);
media.addEventListener('change', (e) => {
  systemDark.value = e.matches;
});

/** 当前是否暗色（system 模式下跟随系统偏好） */
export const isDark = computed(
  () => themeMode.value === 'dark' || (themeMode.value === 'system' && systemDark.value)
);

const apply = (dark) => {
  document.documentElement.classList.toggle('dark', dark);
};

watch(isDark, apply, { immediate: true });

export const setTheme = (mode) => {
  if (!MODES.includes(mode)) return;
  themeMode.value = mode;
  try {
    localStorage.setItem(STORAGE_KEY, mode);
  } catch (e) {}
};

/** light → dark → system 循环 */
export const cycleTheme = () => {
  const idx = MODES.indexOf(themeMode.value);
  setTheme(MODES[(idx + 1) % MODES.length]);
};

export function useTheme() {
  return { themeMode, isDark, setTheme, cycleTheme };
}
