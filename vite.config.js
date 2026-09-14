import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';
import fs from 'fs';

// Vite 会将 rollupOptions.input 的目录结构带进 outDir，
// 导致入口输出为 public/resources/index.html。
// 构建结束后将其扁平化回 public/index.html（后端 IndexController 读取的位置）。
const flattenIndexHtml = {
  name: 'flatten-index-html',
  closeBundle() {
    const src = path.resolve(__dirname, 'public/resources/index.html');
    const dest = path.resolve(__dirname, 'public/index.html');
    if (fs.existsSync(src)) {
      fs.copyFileSync(src, dest);
      fs.rmSync(path.dirname(src), { recursive: true, force: true });
    }
  }
};

export default defineConfig({
  plugins: [vue(), flattenIndexHtml],
  base: '/app/horizon/',
  build: {
    outDir: 'public',
    emptyOutDir: false,
    rollupOptions: {
      input: path.resolve(__dirname, 'resources/index.html'),
      output: {
        entryFileNames: 'js/[name].js',
        chunkFileNames: 'js/[name].js',
        assetFileNames: 'assets/[name].[ext]'
      }
    }
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'resources/js')
    }
  }
});
