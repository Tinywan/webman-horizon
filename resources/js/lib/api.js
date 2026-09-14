import axios from 'axios';
import { t } from '@/composables/useI18n';

/**
 * 统一 API 封装
 * - baseURL 指向 /app/horizon/api
 * - 响应拦截器统一解包 { code, msg, data }，code !== 0 时抛出带 msg 的错误
 */
const http = axios.create({
  baseURL: '/app/horizon/api',
  timeout: 10000,
});

http.interceptors.response.use(
  (response) => {
    const body = response.data;
    if (body && typeof body === 'object' && 'code' in body) {
      if (body.code === 0) {
        return body.data !== undefined ? body.data : body;
      }
      const error = new Error(body.msg || t('common.requestFailed'));
      error.code = body.code;
      throw error;
    }
    return body;
  },
  (error) => {
    const msg = error.response?.data?.msg || error.message || t('common.networkFailed');
    const wrapped = new Error(msg);
    wrapped.status = error.response?.status;
    throw wrapped;
  }
);

const toForm = (obj) => {
  const fd = new FormData();
  Object.entries(obj).forEach(([key, value]) => fd.append(key, value));
  return fd;
};

export default {
  // 总体指标
  getStats: () => http.get('/stats'),
  getThroughput: (queue = 'total', minutes = 60) =>
    http.get('/throughput', { params: { queue, minutes } }),
  getMetrics: (queue = 'default') =>
    http.get('/metrics', { params: { queue } }),

  // 队列
  getQueues: () => http.get('/queues'),
  clearQueue: (queue, type = 'waiting') =>
    http.post('/queues/clear', toForm({ queue, type })),

  // 失败任务
  getFailedJobs: (queue = 'default', page = 1, pageSize = 15) =>
    http.get('/failed-jobs', { params: { queue, page, page_size: pageSize } }),
  retryJob: (queue, index) =>
    http.post('/failed-jobs/retry', toForm({ queue, index })),
  retryAllJobs: (queue) =>
    http.post('/failed-jobs/retry-all', toForm({ queue })),
  deleteJob: (queue, index) =>
    http.delete('/failed-jobs', { data: toForm({ queue, index }) }),
};
