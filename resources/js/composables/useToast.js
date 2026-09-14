import { reactive } from 'vue';

/**
 * 轻量 Toast 通知
 * 用法: toast('重试成功', 'success')
 * ToastContainer 组件挂载于 App.vue 统一渲染
 */
let seq = 0;

export const toasts = reactive([]);

export function toast(message, type = 'success', duration = 3200) {
  const id = ++seq;
  toasts.push({ id, message, type });
  setTimeout(() => {
    const idx = toasts.findIndex((t) => t.id === id);
    if (idx !== -1) toasts.splice(idx, 1);
  }, duration);
}
