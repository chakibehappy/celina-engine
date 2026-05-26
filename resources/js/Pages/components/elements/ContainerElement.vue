<!-- resources/js/Pages/components/elements/ContainerElement.vue -->
<script setup>
import { ref, computed, inject } from 'vue';
import ForgeElement from '../ForgeElement.vue';

const props = defineProps({
  element: Object,
  selectedId: [String, Number],
  activePage: Object,
  database: Array,
  mode: String,
});

const emit = defineEmits(['select', 'updateTree', 'updateData']);
const isOver = ref(false);

const mobileCanvasLayout = inject('mobileCanvasLayout', null);

const isPageRoot = computed(
  () =>
    props.element?.id?.startsWith('root_') &&
    props.activePage?.elements?.[0]?.id === props.element.id
);

const isFlushDropZone = computed(() => isPageRoot.value && !!mobileCanvasLayout);

const droppedBoxMinClass = computed(() =>
  props.element.type === 'container-h' ? 'min-h-[72px]' : 'min-h-[96px]'
);

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

/** Drop on empty container zone must call preventDefault or the browser rejects the drop. */
const handleDrop = (e) => {
  e.preventDefault();
  e.stopPropagation();
  isOver.value = false;

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
    class="w-full h-full min-h-full relative"
    :class="[
      element.type === 'container-v' ? 'flex flex-col' : 'flex flex-row',
      isFlushDropZone ? 'items-stretch justify-start' : '',
      isOver ? 'bg-blue-500/5' : '',
      isFlushDropZone ? 'min-h-full' : ['w-full shrink-0', droppedBoxMinClass],
    ]"
    @dragover.prevent="isOver = true"
    @dragleave="isOver = false"
    @drop="handleDrop"
  >
    <ForgeElement 
      v-for="child in element.children" 
      :key="child.id" 
      :element="child"
      :selectedId="selectedId"
      :activePage="activePage"
      :database="database"
      :mode="mode"
      @select="id => emit('select', id)"
      @updateTree="emit('updateTree')"
      @updateData="emit('updateData')"
    />

    <div
      v-if="element.children.length === 0"
      class="m-0 p-0 w-full box-border"
      :class="[
        isFlushDropZone
          ? 'absolute left-0 right-0 top-0 bottom-0 h-full'
          : ['relative flex items-center justify-center border-2 border-dashed border-gray-300/80 bg-gray-50/80 text-[10px] text-gray-400 uppercase tracking-wide', droppedBoxMinClass],
        isFlushDropZone && isOver ? 'ring-1 ring-inset ring-blue-400/30' : '',
      ]"
    >
      <span v-if="!isFlushDropZone">Drop items here</span>
    </div>
  </div>
</template>