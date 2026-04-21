<!-- resources/js/Pages/components/ForgeCanvas.vue -->
<script setup>
defineProps([
  'activePage', 'pages', 'activePageId', 'expandedNodes', 
  'selectedTemplate', 'templates', 'canvasStyle', 
  'zoomPercentage', 'getElementStyle', 'selectElement',
  'selectedElement', 'database'
]);
const emit = defineEmits([
  'handleZoom', 'startPan', 'doPan', 'stopPan', 
  'save', 'resetZoom', 'update:activePageId', 'toggleExpand',
  'recordHistory', 'moveElement', 'updateData'
]);
</script>

<template>
  <main class="flex-grow relative overflow-hidden bg-[#0a0c10] cursor-grab active:cursor-grabbing"
        @wheel="emit('handleZoom', $event)" @mousedown="emit('startPan', $event)" 
        @mousemove="emit('doPan', $event)" @mouseup="emit('stopPan')" @mouseleave="emit('stopPan')">
    
    <div class="h-10 absolute top-0 w-full border-b border-gray-800 bg-[#1a1d23]/80 backdrop-blur z-10 flex items-center justify-between px-4">
      <div class="text-[10px] font-mono text-gray-500 uppercase flex items-center space-x-2">
        <span :class="['w-2 h-2 rounded-full animate-pulse', activePage?.showPage ? 'bg-green-500' : 'bg-orange-500']"></span>
        <span>Editing Node: <span class="text-blue-400">{{ activePage?.name }}</span></span>
      </div>
      <div class="flex items-center space-x-3">
         <button @click="emit('save')" class="bg-indigo-600 hover:bg-blue-500 text-white text-[9px] font-bold px-3 py-1 rounded uppercase transition-colors">
           Save Dashboard
         </button>
        <div class="text-[10px] font-mono text-gray-400 bg-[#0d0f14] px-2 py-0.5 rounded border border-gray-800">
          {{ zoomPercentage }}
        </div>
        <button @click="emit('resetZoom')" class="p-1 hover:bg-blue-500/20 border border-gray-800 hover:border-blue-500/50 rounded transition-colors group">
          <svg class="w-3.5 h-3.5 text-gray-500 group-hover:text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M15 3h6v6M9 21H3v-6M21 15v6h-6M3 9V3h6" />
          </svg>
        </button>
      </div>
    </div>

    <div class="w-full h-full flex items-center justify-center bg-grid" :style="canvasStyle">      
      <component 
        v-if="selectedTemplate && templates[selectedTemplate] && activePage"
        :is="templates[selectedTemplate]" 
        :activePage="activePage" 
        :pages="pages" 
        :activePageId="activePageId"
        :expandedNodes="expandedNodes"
        :selectedElement="selectedElement" 
        @toggleExpand="id => emit('toggleExpand', id)"
        @update:activePageId="id => emit('update:activePageId', id)"
        @selectElement="id => emit('selectElement', id)" 
        @recordHistory="emit('recordHistory')" 
        class="shadow-2xl pointer-events-auto" 
        :class="{ 'opacity-80 grayscale-[0.2]': !activePage.showPage }"
        :getElementStyle="getElementStyle"
        :selectElement="selectElement"
        :database="database"
        @updateData="$emit('updateData')"
        :mode="'editor'"
      />
    </div>
  </main>
</template>