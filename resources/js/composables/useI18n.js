import { ref } from 'vue';
import zhCN from '@/locales/zh-CN';
import enUS from '@/locales/en-US';

/**
 * 轻量 i18n（无 vue-i18n 依赖）
 * - locale 为响应式 ref，模板中调用 t() 即自动追踪，切换语言整站即时生效
 * - 用户选择持久化到 localStorage（key: horizon-locale），默认 zh-CN
 */
const STORAGE_KEY = 'horizon-locale';
const DEFAULT_LOCALE = 'zh-CN';

const messages = {
  'zh-CN': zhCN,
  'en-US': enUS,
};

const initLocale = () => {
  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored && messages[stored]) return stored;
  } catch (e) {}
  return DEFAULT_LOCALE;
};

export const locale = ref(initLocale());

export const AVAILABLE_LOCALES = [
  { value: 'zh-CN', label: '中文' },
  { value: 'en-US', label: 'English' },
];

const resolve = (dict, key) => {
  return key.split('.').reduce((node, seg) => (node && node[seg] !== undefined ? node[seg] : undefined), dict);
};

/**
 * 翻译
 * @param {string} key 点分路径，如 'failedJobs.retry'
 * @param {Object} params 插值参数，{count: 3} 替换文案中的 {count}
 */
export const t = (key, params = {}) => {
  let text = resolve(messages[locale.value], key);
  if (text === undefined) text = resolve(messages[DEFAULT_LOCALE], key);
  if (text === undefined) return key;
  return String(text).replace(/\{(\w+)\}/g, (_, name) =>
    params[name] !== undefined ? String(params[name]) : `{${name}}`
  );
};

export const setLocale = (value) => {
  if (!messages[value]) return;
  locale.value = value;
  try {
    localStorage.setItem(STORAGE_KEY, value);
  } catch (e) {}
  document.documentElement.lang = value;
};

/** 中英文循环切换 */
export const toggleLocale = () => {
  setLocale(locale.value === 'zh-CN' ? 'en-US' : 'zh-CN');
};

// 初始化 <html lang>
document.documentElement.lang = locale.value;

export function useI18n() {
  return { locale, t, setLocale, toggleLocale, AVAILABLE_LOCALES };
}
