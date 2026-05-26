<!-- Shared mobile canvas: no app header / nav chrome — content only -->
<template>
  <div class="w-full h-full flex flex-col overflow-hidden rounded-2xl" :class="surfaceClass">
    <main
      v-if="activePage?.showPage"
      class="flex-grow flex flex-col overflow-y-auto overflow-x-hidden custom-scroll min-h-0"
    >
      <ForgeElement
        v-for="el in (activePage.elements || [])"
        :key="el.id"
        :element="el"
        :selectedId="selectedElement"
        :activePage="activePage"
        :database="database"
        :mode="mode"
        @select="$emit('selectElement', $event)"
        @updateTree="$emit('recordHistory')"
        @updateData="$emit('updateData')"
      />
      <div
        v-if="!activePage.elements?.length"
        class="flex-grow min-h-[120px] flex flex-col items-center justify-center border-2 border-dashed rounded-2xl m-3 transition-colors"
        :class="emptyClass"
      >
        <span class="font-mono text-[10px] uppercase tracking-widest">{{ emptyLabel }}</span>
      </div>
    </main>
  </div>
</template>

<script setup>
import ForgeElement from './ForgeElement.vue';

defineProps({
  activePage: Object,
  selectedElement: [String, Number],
  database: Array,
  mode: String,
  surfaceClass: { type: String, default: 'bg-white text-gray-900' },
  emptyClass: { type: String, default: 'border-gray-300 text-gray-400' },
  emptyLabel: { type: String, default: 'Drop elements here' },
});

defineEmits(['selectElement', 'recordHistory', 'updateData']);
</script>

<style scoped>
.custom-scroll::-webkit-scrollbar { width: 4px; }
.custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
</style>
