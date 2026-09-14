import * as echarts from 'echarts/core';
import { LineChart } from 'echarts/charts';
import { GridComponent, TooltipComponent } from 'echarts/components';
import { CanvasRenderer } from 'echarts/renderers';

/**
 * 按需注册 ECharts 模块（tree-shaking，避免全量打包）
 * 当前面板仅使用折线/面积图，如需其他图表类型在此追加注册
 */
echarts.use([LineChart, GridComponent, TooltipComponent, CanvasRenderer]);

export default echarts;
