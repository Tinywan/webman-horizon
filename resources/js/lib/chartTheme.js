import { reactive, watch } from 'vue';
import echarts from './echarts';
import { isDark } from '@/composables/useTheme';

/**
 * ECharts 主题片段（亮/暗双套）
 * chartTheme 为响应式对象，随主题切换自动更新；
 * 页面在 computed option 中展开使用（...chartTheme.tooltip 等），
 * 主题变化会触发 option 重算，VChart 侦听后 notMerge 重绘。
 */
const palettes = {
  dark: {
    tooltipBg: 'rgba(17, 24, 39, 0.95)',
    tooltipBorder: 'rgba(255, 255, 255, 0.08)',
    tooltipText: '#e5e7eb',
    tooltipShadow: '0 8px 24px rgba(0, 0, 0, 0.5)',
    axisPointer: 'rgba(99, 102, 241, 0.4)',
    axisLine: 'rgba(255, 255, 255, 0.08)',
    splitLine: 'rgba(255, 255, 255, 0.05)',
    axisLabel: '#6b7280',
    symbolBorder: '#0b0f19',
  },
  light: {
    tooltipBg: 'rgba(255, 255, 255, 0.96)',
    tooltipBorder: 'rgba(15, 23, 42, 0.08)',
    tooltipText: '#334155',
    tooltipShadow: '0 8px 24px rgba(15, 23, 42, 0.12)',
    axisPointer: 'rgba(99, 102, 241, 0.45)',
    axisLine: 'rgba(15, 23, 42, 0.14)',
    splitLine: 'rgba(15, 23, 42, 0.06)',
    axisLabel: '#94a3b8',
    symbolBorder: '#ffffff',
  },
};

const build = (p) => ({
  tooltip: {
    trigger: 'axis',
    backgroundColor: p.tooltipBg,
    borderColor: p.tooltipBorder,
    borderWidth: 1,
    padding: [10, 14],
    textStyle: { color: p.tooltipText, fontSize: 12 },
    extraCssText: `border-radius: 10px; box-shadow: ${p.tooltipShadow};`,
    axisPointer: {
      type: 'line',
      lineStyle: { color: p.axisPointer, type: 'dashed' },
    },
  },
  xAxis: {
    type: 'category',
    boundaryGap: false,
    axisLine: { lineStyle: { color: p.axisLine } },
    axisTick: { show: false },
    axisLabel: { color: p.axisLabel, fontSize: 11, fontFamily: 'ui-monospace, monospace' },
  },
  yAxis: {
    type: 'value',
    splitLine: { lineStyle: { color: p.splitLine } },
    axisLabel: { color: p.axisLabel, fontSize: 11, fontFamily: 'ui-monospace, monospace' },
  },
});

const currentPalette = () => palettes[isDark.value ? 'dark' : 'light'];

export const chartTheme = reactive(build(currentPalette()));

watch(isDark, () => {
  Object.assign(chartTheme, build(currentPalette()));
});

/** 网格布局（与主题无关） */
export const baseGrid = {
  left: '2%',
  right: '3%',
  bottom: '2%',
  top: '12%',
  containLabel: true,
};

/** 渐变面积填充 */
export const areaGradient = (hex, from = 0.35, to = 0) =>
  new echarts.graphic.LinearGradient(0, 0, 0, 1, [
    { offset: 0, color: hexToRgba(hex, from) },
    { offset: 1, color: hexToRgba(hex, to) },
  ]);

const hexToRgba = (hex, alpha) => {
  const n = parseInt(hex.replace('#', ''), 16);
  const r = (n >> 16) & 255;
  const g = (n >> 8) & 255;
  const b = n & 255;
  return `rgba(${r}, ${g}, ${b}, ${alpha})`;
};

/** 平滑渐变面积折线序列（在 computed 中调用，随主题重算） */
export const areaSeries = (name, data, color = '#6366f1') => ({
  name,
  type: 'line',
  smooth: true,
  showSymbol: false,
  symbol: 'circle',
  symbolSize: 6,
  data,
  lineStyle: { width: 2, color },
  itemStyle: { color, borderColor: currentPalette().symbolBorder, borderWidth: 2 },
  areaStyle: { color: areaGradient(color) },
  emphasis: { focus: 'series' },
});
