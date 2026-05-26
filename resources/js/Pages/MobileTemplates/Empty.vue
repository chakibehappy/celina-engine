<template>
  <div class="mobile-empty-canvas w-full h-full min-h-full flex flex-col overflow-hidden bg-white m-0 p-0">
    <main
      v-if="activePage?.showPage"
      class="w-full h-full min-h-full flex flex-col items-stretch justify-start overflow-hidden m-0 p-0"
    >
      <ForgeElement
        v-for="el in (activePage.elements || [])"
        :key="el.id"
        :element="el"
        :selectedId="selectedElement"
        :activePage="activePage"
        :database="database"
        :mode="mode"
        class="mobile-empty-root w-full min-h-full h-full flex-1 self-stretch"
        @select="$emit('selectElement', $event)"
        @updateTree="$emit('recordHistory')"
        @updateData="$emit('updateData')"
      />
    </main>
  </div>
</template>

<script setup>
import { provide } from 'vue';
import ForgeElement from '../components/ForgeElement.vue';

defineProps(['activePage', 'selectedElement', 'database', 'mode']);
defineEmits(['selectElement', 'recordHistory', 'updateData']);

provide('mobileCanvasLayout', { flushHorizontal: true, alignTop: true });
</script>

<style scoped>
.mobile-empty-canvas,
.mobile-empty-canvas :deep(.mobile-empty-root) {
  padding-left: 0 !important;
  padding-right: 0 !important;
}
</style>
