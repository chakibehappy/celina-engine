<!-- resources/js/Pages/components/DashboardTemplates/LightSidebar.vue -->
<template>
  <div class="w-full h-full max-w-5xl bg-white rounded-lg shadow-2xl overflow-hidden flex text-gray-800 border border-gray-200">
    <aside class="w-60 bg-gray-50 border-r border-gray-200 p-5 flex flex-col">
      <div class="flex items-center space-x-2 mb-8 px-2">
        <div class="w-7 h-7 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-[10px]">C</div>
        <span class="font-bold text-sm tracking-tight text-gray-900">CELINA_PRO</span>
      </div>
      
      <nav class="space-y-1 flex-grow overflow-y-auto pr-2 custom-scroll">
        <div v-for="node in pages" :key="node.id">
          <div 
            @click="isEditor 
            ? (node.children.length ? $emit('toggleExpand', node.id) : $emit('update:activePageId', node.id))
            : (node.children.length ? null : $emit('navigate', node.slug))
            "
            :class="[
              'px-3 py-2 rounded-md text-[11px] font-bold transition-all flex items-center justify-between cursor-pointer',
              !node.children.length && node.id === activePageId ? 'bg-blue-100 text-blue-700' : 'text-gray-500 hover:bg-gray-200/50'
            ]"
          >
            <div class="flex items-center space-x-2">
              <span v-if="node.children.length" class="text-[8px] transition-transform" :class="expandedNodes.has(node.id) ? 'rotate-0' : '-rotate-90'">▼</span>
              <span>{{ node.name }}</span>
            </div>
          </div>

          <div v-if="node.children.length && expandedNodes.has(node.id)" class="ml-4 mt-1 space-y-1 border-l border-gray-200">
            <div v-for="child in node.children" :key="child.id">
              <div 
                @click="child.children.length ? $emit('toggleExpand', child.id) : $emit('update:activePageId', child.id)"
                :class="[
                  'ml-3 px-3 py-1.5 rounded-md text-[10px] font-medium transition-all cursor-pointer',
                  !child.children.length && child.id === activePageId ? 'bg-blue-50 text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-800'
                ]"
              >
                <div class="flex items-center space-x-2">
                  <span v-if="child.children.length" class="text-[8px] opacity-50" :class="expandedNodes.has(child.id) ? '' : '-rotate-90'">▼</span>
                  <span>{{ child.name }}</span>
                </div>
              </div>

              <div v-if="child.children.length && expandedNodes.has(child.id)" class="ml-6 mt-1 space-y-1 border-l border-gray-200">
                <div v-for="grandChild in child.children" :key="grandChild.id"
                  @click="$emit('update:activePageId', grandChild.id)"
                  :class="[
                    'ml-3 px-3 py-1.5 rounded-md text-[10px] transition-all cursor-pointer',
                    grandChild.id === activePageId ? 'text-blue-600 font-bold' : 'text-gray-400 hover:text-gray-700'
                  ]"
                >
                  {{ grandChild.name }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </nav>

      <div class="mt-auto pt-4 border-t border-gray-100 opacity-40 grayscale">
        <div class="h-2 w-16 bg-gray-300 rounded mb-2"></div>
        <div class="h-2 w-10 bg-gray-200 rounded"></div>
      </div>
    </aside>

    <main 
      v-if="activePage?.showPage" 
      class="flex-grow h-full bg-white relative flex flex-col overflow-hidden"
    >
      <div class="flex-grow overflow-y-auto custom-scroll">
        <ForgeElement 
          v-for="el in (activePage.elements || [])" 
          :key="el.id" 
          :element="el" 
          :selectedId="selectedElement"
          :activePage="activePage"
          @select="selectElement"
          @updateTree="$emit('recordHistory')"
          :database="database"
          :mode="mode"
        />
        <div v-if="!activePage.elements || activePage.elements.length === 0" class="h-full flex flex-col items-center justify-center text-gray-400">
          <p class="text-[10px] uppercase tracking-widest font-bold">No Elements</p>
          <span class="text-[9px]">Drag elements from the left sidebar to start building</span>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>

import { computed } from 'vue';
import ForgeElement from '../components/ForgeElement.vue';

// We added selectedElement to props and selectElement / recordHistory to emits
const props = defineProps([
  'activePage', 
  'pages', 
  'activePageId', 
  'expandedNodes', 
  'getElementStyle', 
  'selectElement', 
  'selectedElement',
  'database',
  'mode'
]);

const emit = defineEmits([
  'update:activePageId', 
  'toggleExpand', 
  'selectElement', 
  'recordHistory'
]);

const isEditor = computed(() => props.mode === 'editor');
</script>

<style scoped>
.custom-scroll::-webkit-scrollbar { width: 3px; }
.custom-scroll::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
</style>