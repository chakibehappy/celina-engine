<!-- resources/js/Pages/components/elements/ContainerElement.vue -->
<script setup>
import { ref } from 'vue';
// We import ForgeElement back for recursion
import ForgeElement from '../ForgeElement.vue';

const props = defineProps({
  element: Object,
  selectedId: [String, Number],
  activePage: Object,
  database: Array
});

const emit = defineEmits(['select', 'updateTree', 'updateData']);
const isOver = ref(false);
</script>

<template>
  <div 
    class="flex-1 w-full min-h-[40px]"
    :class="[
      element.type === 'container-v' ? 'flex flex-col' : 'flex flex-row',
      isOver ? 'bg-blue-500/5' : ''
    ]"
    @dragover.prevent="isOver = true"
    @dragleave="isOver = false"
    @drop="isOver = false"
  >
    <ForgeElement 
      v-for="child in element.children" 
      :key="child.id" 
      :element="child"
      :selectedId="selectedId"
      :activePage="activePage"
      :database="database"
      @select="id => emit('select', id)"
      @updateTree="emit('updateTree')"
      @updateData="emit('updateData')"
    />

    <div v-if="element.children.length === 0" 
      class="flex-1 flex items-center justify-center p-4 border-2 border-dashed bg-[#00000055] border-white/20 text-[10px] text-white uppercase"
    >
      Drop items here
    </div>
  </div>
</template>