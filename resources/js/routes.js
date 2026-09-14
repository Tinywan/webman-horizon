import { createRouter, createWebHistory } from 'vue-router';

const Dashboard = () => import('./screens/dashboard/index.vue');
const Monitoring = () => import('./screens/monitoring/index.vue');
const Metrics = () => import('./screens/metrics/index.vue');
const RecentJobs = () => import('./screens/recentJobs/index.vue');
const FailedJobs = () => import('./screens/failedJobs/index.vue');
const Batches = () => import('./screens/batches/index.vue');

// titleKey / subtitleKey 为 i18n 键，顶栏通过 t() 解析
const routes = [
  { path: '/', redirect: '/dashboard' },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: Dashboard,
    meta: { titleKey: 'nav.dashboard', subtitleKey: 'dashboard.subtitle' },
  },
  {
    path: '/monitoring',
    name: 'monitoring',
    component: Monitoring,
    meta: { titleKey: 'nav.monitoring', subtitleKey: 'monitoring.subtitle' },
  },
  {
    path: '/metrics',
    name: 'metrics',
    component: Metrics,
    meta: { titleKey: 'nav.metrics', subtitleKey: 'metrics.subtitle' },
  },
  {
    path: '/recent-jobs',
    name: 'recent-jobs',
    component: RecentJobs,
    meta: { titleKey: 'nav.recentJobs', subtitleKey: 'recentJobs.subtitle' },
  },
  {
    path: '/failed-jobs',
    name: 'failed-jobs',
    component: FailedJobs,
    meta: { titleKey: 'nav.failedJobs', subtitleKey: 'failedJobs.subtitle' },
  },
  {
    path: '/batches',
    name: 'batches',
    component: Batches,
    meta: { titleKey: 'nav.batches', subtitleKey: 'batches.subtitle' },
  },
];

export default createRouter({
  history: createWebHistory('/app/horizon/'),
  routes,
});
