<!-- resources/js/Pages/components/ForgeElement.vue -->
<script setup>
import { ref, computed, defineAsyncComponent } from 'vue';

// Auto-register components from the elements folder
const components = {
  'text': defineAsyncComponent(() => import('./elements/TextElement.vue')),
  'data-table': defineAsyncComponent(() => import(props.mode === 'build' ?  './elements/DataTableBuild.vue' : './elements/DataTableElement.vue')),
//   'data-table': defineAsyncComponent(() => import('./elements/DataTableElement.vue')),
  'container-v': defineAsyncComponent(() => import('./elements/ContainerElement.vue')),
  'container-h': defineAsyncComponent(() => import('./elements/ContainerElement.vue')),
};

const props = defineProps({
  element: Object,
  selectedId: [String, Number],
  activePage: Object,
  database: Array,
  mode: String,
});

const emit = defineEmits(['select', 'updateTree', 'updateData']);

const isDragging = ref(false); 
const isSelected = computed(() => props.selectedId === props.element.id);

/** * DRAG & DROP LOGIC (Core functionality)
 */
const removeElementById = (list, id) => {
  for (let i = 0; i < list.length; i++) {
    if (list[i].id === id) return list.splice(i, 1)[0];
    if (list[i].children) {
      const found = removeElementById(list[i].children, id);
      if (found) return found;
    }
  }
  return null;
};

const isDescendant = (parent, id) => {
  if (!parent.children) return false;
  for (const child of parent.children) {
    if (child.id === id || isDescendant(child, id)) return true;
  }
  return false;
};

const handleDragStart = (e) => {
  e.stopPropagation();
  e.dataTransfer.setData('payload', JSON.stringify({ type: 'move', elementId: props.element.id }));
  setTimeout(() => { isDragging.value = true; }, 0);
};

const handleDrop = (e) => {
  e.preventDefault();
  e.stopPropagation();
  const rawData = e.dataTransfer.getData('payload');
  if (!rawData || !props.element.children) return;

  const payload = JSON.parse(rawData);
  let newElement = null;

  if (payload.type === 'new') newElement = payload.data;
  if (payload.type === 'move') {
    if (payload.elementId === props.element.id || isDescendant(props.element, payload.elementId)) return;
    newElement = removeElementById(props.activePage.elements, payload.elementId);
  }

  if (newElement) {
    props.element.children.push(newElement);
    emit('updateTree');
  }
};
</script>

<template>
  <div 
    draggable="true"
    @dragstart="handleDragStart"
    @dragend="isDragging = false" 
    @click.stop="emit('select', element.id)"
    @dragover.prevent
    @drop="handleDrop"
    :class="[
      'relative min-h-[20px] transition-all border',
      isSelected ? 'border-blue-500 ring-1 ring-blue-500/20' : 'border-transparent hover:border-blue-500/30',
      isDragging ? 'opacity-0 scale-0' : 'opacity-100' 
    ]"
    :style="element.props.style"
  >
    <div v-if="isSelected" class="absolute -top-3 left-0 bg-blue-500 text-[8px] text-white px-1 uppercase z-10">
      {{ element.type }}
    </div>

    <component 
      :is="components[element.type]"
      :element="element"
      :selectedId="selectedId"
      :activePage="activePage"
      :database="database"
      @select="id => emit('select', id)"
      @updateTree="emit('updateTree')"
      @updateData="emit('updateData')"
    />
  </div>
</template>