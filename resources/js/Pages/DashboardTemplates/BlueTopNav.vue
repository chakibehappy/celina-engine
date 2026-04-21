<template>
  <div class="w-full h-full max-w-5xl bg-[#f8fafc] rounded-lg shadow-2xl overflow-hidden flex flex-col text-slate-800">
    <header class="h-16 bg-blue-600 px-6 flex items-center justify-between shadow-lg relative z-50">
      <div class="flex items-center space-x-8">
        <div class="text-white font-bold tracking-tighter text-xl">CELINA_PRO</div>
        <nav class="flex space-x-6">
          <div v-for="p in pages" :key="p.id" class="relative" v-click-outside="() => closeMenu(p.id)">
            <button 
              @click="p.children.length ? toggleMenu(p.id) : $emit('update:activePageId', p.id)"
              :class="['text-xs font-bold uppercase tracking-wider transition-all flex items-center space-x-1 outline-none', 
                       p.id === activePageId || activeMenu === p.id ? 'text-white' : 'text-blue-200 opacity-60 hover:opacity-100']"
            >
              <span>{{ p.name }}</span>
              <span v-if="p.children.length" class="text-[8px] opacity-50">▼</span>
            </button>

            <div v-if="activeMenu === p.id && p.children.length" 
                 class="absolute top-full left-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-100 py-2 text-slate-700 animate-in fade-in slide-in-from-top-2 duration-200">
              <div v-for="child in p.children" :key="child.id" class="relative group">
                <button 
                  @click="child.children.length ? null : selectPage(child.id)"
                  class="w-full text-left px-4 py-2 text-[11px] font-bold hover:bg-blue-50 hover:text-blue-600 flex justify-between items-center"
                >
                  {{ child.name }}
                  <span v-if="child.children.length" class="text-[8px]">▶</span>
                </button>
                
                <div v-if="child.children.length" 
                     class="absolute left-full top-0 ml-px w-48 bg-white rounded-lg shadow-xl border border-gray-100 py-2 opacity-0 group-hover:opacity-100 pointer-events-none group-hover:pointer-events-auto transition-opacity">
                  <button v-for="gc in child.children" :key="gc.id"
                    @click="selectPage(gc.id)"
                    class="w-full text-left px-4 py-2 text-[11px] font-bold hover:bg-blue-50 hover:text-blue-600"
                  >
                    {{ gc.name }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </nav>
      </div>
    </header>

    <main v-if="activePage?.showPage" class="flex-grow flex flex-col overflow-hidden">
      
      <div class="flex-grow overflow-y-auto custom-scroll">
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
          <div v-if="!activePage.elements?.length" class="h-80 flex items-center justify-center border-2 border-dashed border-blue-100 rounded-2xl text-blue-200 font-mono text-[10px] uppercase tracking-widest">
            Canvas Empty: {{ activePage?.slug }}
          </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import ForgeElement from '../components/ForgeElement.vue';

const props = defineProps([
  'activePage', 'pages', 'activePageId', 'expandedNodes', 
  'selectedElement', 'database', 'mode'
]);
const emit = defineEmits(['update:activePageId', 'toggleExpand', 'selectElement', 'recordHistory']);

const activeMenu = ref(null);
const toggleMenu = (id) => {
  activeMenu.value = activeMenu.value === id ? null : id;
  emit('toggleExpand', id);
};
const closeMenu = (id) => { if (activeMenu.value === id) activeMenu.value = null; };
const selectPage = (id) => {
  emit('update:activePageId', id);
  activeMenu.value = null;
};

const vClickOutside = {
  mounted(el, binding) {
    el.clickOutsideEvent = (event) => {
      if (!(el === event.target || el.contains(event.target))) binding.value();
    };
    document.addEventListener('click', el.clickOutsideEvent);
  },
  unmounted(el) { document.removeEventListener('click', el.clickOutsideEvent); },
};
</script>