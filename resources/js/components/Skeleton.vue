<template>
  <!-- 指标卡骨架 -->
  <div v-if="variant === 'cards'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <div v-for="i in count" :key="i" class="card p-5 animate-pulse">
      <div class="flex items-start justify-between">
        <div class="h-3 w-24 rounded bg-surface-strong"></div>
        <div class="w-8 h-8 rounded-lg bg-surface-strong"></div>
      </div>
      <div class="mt-4 h-8 w-20 rounded bg-surface-strong"></div>
      <div class="mt-3 h-3 w-32 rounded bg-surface-strong"></div>
    </div>
  </div>

  <!-- 图表骨架 -->
  <div v-else-if="variant === 'chart'" class="card p-6 animate-pulse">
    <div class="h-4 w-48 rounded bg-surface-strong"></div>
    <div class="mt-2 h-3 w-64 rounded bg-surface-strong"></div>
    <div class="mt-6 h-64 rounded-lg bg-surface flex items-end gap-2 px-6 pb-4">
      <div
        v-for="i in 24"
        :key="i"
        class="flex-1 rounded-t bg-surface-strong"
        :style="{ height: `${20 + ((i * 37) % 60)}%` }"
      ></div>
    </div>
  </div>

  <!-- 表格行骨架 -->
  <div v-else-if="variant === 'rows'" class="card overflow-hidden animate-pulse">
    <div class="px-6 py-4 border-b border-line">
      <div class="h-4 w-40 rounded bg-surface-strong"></div>
    </div>
    <div v-for="i in count" :key="i" class="px-6 py-4 border-b border-line flex items-center gap-6">
      <div class="h-4 w-28 rounded bg-surface-strong"></div>
      <div class="h-5 w-12 rounded bg-surface-strong"></div>
      <div class="h-5 w-12 rounded bg-surface-strong"></div>
      <div class="h-5 w-12 rounded bg-surface-strong"></div>
      <div class="flex-1"></div>
      <div class="h-4 w-16 rounded bg-surface-strong"></div>
    </div>
  </div>

  <!-- 通用块骨架 -->
  <div v-else class="card p-6 animate-pulse">
    <div class="h-4 w-40 rounded bg-surface-strong"></div>
    <div class="mt-4 space-y-3">
      <div v-for="i in count" :key="i" class="h-4 rounded bg-surface-strong" :style="{ width: `${100 - i * 12}%` }"></div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  variant: { type: String, default: 'block' }, // cards | chart | rows | block
  count: { type: Number, default: 4 },
});
</script>
