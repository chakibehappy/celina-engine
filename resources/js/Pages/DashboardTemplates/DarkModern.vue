<template>
  <div class="w-full h-full max-w-5xl bg-[#0f172a] rounded-lg shadow-2xl overflow-hidden flex text-slate-100 border border-slate-800">
    <aside class="w-20 bg-[#020617] flex flex-col items-center py-8 border-r border-slate-800 z-50">
      <div class="w-10 h-10 bg-indigo-500 rounded-2xl mb-12 flex items-center justify-center font-black text-xl shadow-lg shadow-indigo-500/20">C</div>
      
      <nav class="space-y-6 flex-grow">
        <div v-for="p in pages" :key="p.id" class="relative group flex justify-center">
          <button 
            @click="p.children.length ? $emit('toggleExpand', p.id) : $emit('update:activePageId', p.id)"
            :class="['w-10 h-10 rounded-xl transition-all duration-300 border flex items-center justify-center overflow-hidden outline-none relative z-20', 
                     p.id === activePageId || expandedNodes.has(p.id) ? 'bg-indigo-600 border-indigo-400 scale-110 shadow-lg' : 'bg-slate-900 border-slate-800 opacity-40 hover:opacity-100']">
            <span class="text-[10px] font-black uppercase">{{ p.name.substring(0, 2) }}</span>
          </button>

          <div v-if="p.children.length" 
               class="absolute left-10 top-[-20px] pt-[20px] pb-[20px] pl-10 opacity-0 group-hover:opacity-100 pointer-events-none group-hover:pointer-events-auto transition-opacity duration-200 z-50">
            
            <div class="bg-[#0f172a] border border-slate-800 p-2 rounded-lg shadow-2xl min-w-[160px] relative">
              <div class="text-[9px] font-black text-indigo-400 uppercase mb-2 px-2 tracking-widest">{{ p.name }}</div>
              <div class="space-y-1">
                <div v-for="child in p.children" :key="child.id">
                  <button 
                    @click="child.children.length ? null : $emit('update:activePageId', child.id)"
                    :class="[
                      'w-full text-left px-3 py-1.5 rounded text-[10px] font-bold transition-colors flex justify-between items-center', 
                      child.children.length ? 'text-slate-500 cursor-default' : (activePageId === child.id ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white pointer-events-auto')
                    ]"
                  >
                    <span>{{ child.name }}</span>
                    <span v-if="child.children.length" class="text-[8px] opacity-20">▼</span>
                  </button>

                  <div v-if="child.children.length" class="ml-2 mt-1 pl-2 border-l border-slate-800 space-y-0.5">
                    <button v-for="gc in child.children" :key="gc.id"
                      @click="$emit('update:activePageId', gc.id)"
                      :class="['w-full text-left px-2 py-1 rounded text-[9px] transition-colors', 
                               activePageId === gc.id ? 'text-indigo-400 font-bold bg-indigo-500/10' : 'text-slate-600 hover:text-slate-300']"
                    >
                      # {{ gc.name }}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <span v-else class="absolute left-16 px-2 py-1 bg-indigo-600 text-[10px] font-bold rounded opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity whitespace-nowrap z-50">
            {{ p.name }}
          </span>
        </div>
      </nav>
    </aside>

    <main v-if="activePage?.showPage" class="flex-grow flex flex-col bg-[#0f172a] overflow-y-auto custom-scroll">
      <ForgeElement 
          v-for="el in (activePage.elements || [])" 
          :key="el.id" 
          :element="el" 
          :selectedId="selectedElement"
          :activePage="activePage"
          @select="$emit('selectElement', $event)"
          @updateTree="$emit('recordHistory')"
          :database="database"
          :mode="mode"
        />
        <div v-if="!activePage.elements?.length" class="h-full min-h-[300px] flex flex-col items-center justify-center border-2 border-dashed border-slate-800 rounded-2xl group hover:border-indigo-500/50 transition-colors">
          <div class="w-16 h-1 bg-slate-800 mb-8 group-hover:bg-indigo-500 transition-colors"></div>
          <span class="text-slate-600 font-mono text-xs uppercase tracking-[0.2em]">Mount point for modules</span>
        </div>
    </main>
  </div>
</template>

<script setup>
import ForgeElement from '../components/ForgeElement.vue';
const props = defineProps([
  'activePage', 'pages', 'activePageId', 'expandedNodes', 
  'selectedElement', 'database', 'mode'
]);
const emit = defineEmits(['update:activePageId', 'toggleExpand', 'selectElement', 'recordHistory']);
</script>