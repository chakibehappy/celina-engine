<!-- resources/js/Pages/components/ForgeCanvas.vue -->
<script setup>
import { computed } from 'vue';
import { DEVICE_PRESET_GROUPS, findDevicePreset } from './devicePresets.js';
import DeviceFrame from './DeviceFrame.vue';

const props = defineProps({
  activePage: Object,
  pages: Array,
  activePageId: [Number, String],
  expandedNodes: Object,
  selectedTemplate: String,
  templates: Object,
  canvasStyle: Object,
  zoomPercentage: String,
  getElementStyle: Function,
  selectElement: Function,
  selectedElement: [String, Number],
  database: Array,
  showDevicePicker: { type: Boolean, default: false },
  devicePresetId: { type: String, default: '' },
  saveLabel: { type: String, default: 'Save Dashboard' },
});

const emit = defineEmits([
  'handleZoom', 'startPan', 'doPan', 'stopPan',
  'save', 'resetZoom', 'update:activePageId', 'toggleExpand',
  'recordHistory', 'moveElement', 'updateData',
  'update:devicePresetId',
]);

const activeDevice = computed(() =>
  props.showDevicePicker ? findDevicePreset(props.devicePresetId) : null
);

const onDeviceChange = (e) => {
  emit('update:devicePresetId', e.target.value);
};

const templateBindings = computed(() => ({
  activePage: props.activePage,
  pages: props.pages,
  activePageId: props.activePageId,
  expandedNodes: props.expandedNodes,
  selectedElement: props.selectedElement,
  getElementStyle: props.getElementStyle,
  selectElement: props.selectElement,
  database: props.database,
  mode: 'editor',
}));
</script>

<template>
  <main
    class="flex-grow relative overflow-hidden bg-[#0a0c10] cursor-grab active:cursor-grabbing"
    @wheel="emit('handleZoom', $event)"
    @mousedown="emit('startPan', $event)"
    @mousemove="emit('doPan', $event)"
    @mouseup="emit('stopPan')"
    @mouseleave="emit('stopPan')"
  >
    <div class="h-10 absolute top-0 w-full border-b border-gray-800 bg-[#1a1d23]/80 backdrop-blur z-10 flex items-center justify-between px-4 gap-3">
      <div class="text-[10px] font-mono text-gray-500 uppercase flex items-center gap-2 min-w-0 flex-1">
        <span :class="['w-2 h-2 rounded-full animate-pulse shrink-0', activePage?.showPage ? 'bg-green-500' : 'bg-orange-500']"></span>
        <span class="truncate shrink-0">
          Editing Node: <span class="text-blue-400">{{ activePage?.name }}</span>
        </span>

        <template v-if="showDevicePicker && activeDevice">
          <span class="text-gray-700 shrink-0">|</span>
          <select
            :value="devicePresetId"
            @change="onDeviceChange"
            @mousedown.stop
            @click.stop
            class="max-w-[220px] bg-[#0d0f14] border border-gray-700 text-[10px] px-2 py-1 rounded outline-none text-gray-300 focus:border-blue-500 truncate"
          >
            <optgroup v-for="group in DEVICE_PRESET_GROUPS" :key="group.label" :label="group.label">
              <option v-for="opt in group.options" :key="opt.id" :value="opt.id">
                {{ opt.label }}
              </option>
            </optgroup>
          </select>
          <span class="text-[9px] text-gray-600 font-mono shrink-0 hidden sm:inline">
            {{ activeDevice.width }}×{{ activeDevice.height }}
            <span class="text-gray-700">·</span>
            {{ activeDevice.orientation }}
          </span>
        </template>
      </div>

      <div class="flex items-center space-x-3 shrink-0">
        <button
          @click.stop="emit('save')"
          class="bg-indigo-600 hover:bg-blue-500 text-white text-[9px] font-bold px-3 py-1 rounded uppercase transition-colors"
        >
          {{ saveLabel }}
        </button>
        <div class="text-[10px] font-mono text-gray-400 bg-[#0d0f14] px-2 py-0.5 rounded border border-gray-800">
          {{ zoomPercentage }}
        </div>
        <button
          @click.stop="emit('resetZoom')"
          class="p-1 hover:bg-blue-500/20 border border-gray-800 hover:border-blue-500/50 rounded transition-colors group"
        >
          <svg class="w-3.5 h-3.5 text-gray-500 group-hover:text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M15 3h6v6M9 21H3v-6M21 15v6h-6M3 9V3h6" />
          </svg>
        </button>
      </div>
    </div>

    <div class="w-full h-full flex items-center justify-center bg-grid pt-10" :style="canvasStyle">
      <DeviceFrame
        v-if="showDevicePicker && activeDevice && selectedTemplate && templates[selectedTemplate] && activePage"
        :device="activeDevice"
        :edge-to-edge="selectedTemplate === 'Empty'"
        @mousedown.stop
        @click.stop
      >
        <component
          :is="templates[selectedTemplate]"
          v-bind="templateBindings"
          class="pointer-events-auto w-full h-full rounded-2xl"
          :class="{ 'opacity-80 grayscale-[0.2]': !activePage.showPage }"
          @toggleExpand="id => emit('toggleExpand', id)"
          @update:activePageId="id => emit('update:activePageId', id)"
          @selectElement="id => emit('selectElement', id)"
          @recordHistory="emit('recordHistory')"
          @updateData="$emit('updateData')"
        />
      </DeviceFrame>

      <component
        v-else-if="selectedTemplate && templates[selectedTemplate] && activePage"
        :is="templates[selectedTemplate]"
        v-bind="templateBindings"
        class="shadow-2xl pointer-events-auto rounded-2xl"
        :class="{ 'opacity-80 grayscale-[0.2]': !activePage.showPage }"
        @toggleExpand="id => emit('toggleExpand', id)"
        @update:activePageId="id => emit('update:activePageId', id)"
        @selectElement="id => emit('selectElement', id)"
        @recordHistory="emit('recordHistory')"
        @updateData="$emit('updateData')"
      />
    </div>
  </main>
</template>

<style scoped>
.bg-grid {
  background-image: radial-gradient(#1f2937 1px, transparent 1px);
  background-size: 20px 20px;
}
</style>
