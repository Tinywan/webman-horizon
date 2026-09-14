<template>
  <div ref="el" class="w-full" :style="{ height }"></div>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';
import echarts from '@/lib/echarts';
import { isDark } from '@/composables/useTheme';

const props = defineProps({
  option: { type: Object, required: true },
  height: { type: String, default: '18rem' },
});

const el = ref(null);
let chart = null;
let resizeObserver = null;

onMounted(() => {
  chart = echarts.init(el.value);
  chart.setOption(props.option);

  resizeObserver = new ResizeObserver(() => {
    chart && chart.resize();
  });
  resizeObserver.observe(el.value);
});

watch(
  () => props.option,
  (opt) => {
    chart && chart.setOption(opt, { notMerge: false });
  },
  { deep: true }
);

// 主题切换：不重合并，彻底清除旧的坐标轴/tooltip 配色
watch(isDark, () => {
  chart && chart.setOption(props.option, { notMerge: true });
});

onBeforeUnmount(() => {
  resizeObserver && resizeObserver.disconnect();
  chart && chart.dispose();
  chart = null;
});
</script>
