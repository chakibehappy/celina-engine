<script setup>
defineProps(['pages', 'activePageId', 'expandedNodes', 'templates', 'selectedTemplate']);
const emit = defineEmits(['update:activePageId', 'toggleExpand', 'addPage', 'update:selectedTemplate']);

const elementGroups = {
  "Layout": [
    { type: 'container-v', label: 'Vertical Box', icon: 'V' },
    { type: 'container-h', label: 'Horizontal Box', icon: 'H' },
  ],
  "UI Elements": [
    { type: 'text', label: 'Text Block', icon: 'T' },
    { type: 'button', label: 'Button', icon: 'B' },
  ],
  "Widgets": [
    { type: 'data-table', label: 'Data Table', icon: 'DT' },
    { type: 'chart', label: 'Chart', icon: 'C' },
  ]
};

const handleDragStart = (e, item) => {
  const blueprint = {
    type: item.type,
    id: `el_${Date.now()}`,
    props: {
      style: { padding: '10px' },
      content: item.type === 'text' ? 'New Text' : ''
    },
    children: item.type.includes('container') ? [] : null
  };

  // ✅ CHANGED HERE
  e.dataTransfer.setData('payload', JSON.stringify({
    type: 'new',
    data: blueprint
  }));
};

</script>

<template>
  <aside class="w-64 border-r border-gray-800 bg-[#14171c] flex flex-col z-20">
    
    <!-- TOP HALF (UNCHANGED) -->
    <div class="h-1/2 flex flex-col border-b border-gray-800">
      <div class="p-3 bg-[#1a1d23] border-b border-gray-800 flex justify-between items-center shrink-0">
        <h3 class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Layout Template</h3>
      </div>

      <div class="p-3 border-t border-gray-800 bg-[#0d0f14]">
        <select :value="selectedTemplate" @change="emit('update:selectedTemplate', $event.target.value)"
          class="w-full bg-[#1a1d23] border border-gray-700 text-[10px] p-1.5 rounded outline-none text-gray-400">
          <option v-for="(comp, name) in templates" :key="name" :value="name">{{ name }}</option>
        </select>
      </div>

      <div class="p-2 bg-[#1a1d23] border-b border-gray-800 flex justify-between items-center shrink-0">
        <h3 class="text-[10px] font-bold uppercase tracking-widest text-gray-500">App Navigation</h3>
        <button @click="emit('addPage')" class="text-blue-500 hover:text-blue-400 font-bold">+</button>
      </div>

      <div class="flex-grow overflow-y-auto p-2 custom-scrollbar">
        <div class="space-y-0.5">
          <div v-for="node in pages" :key="node.id">
            <div @click="emit('update:activePageId', node.id)"
              :class="['group flex items-center justify-between p-1.5 rounded cursor-pointer transition-all border border-transparent', 
                       activePageId === node.id ? 'bg-blue-600/20 text-blue-400 border-blue-500/30' : 'hover:bg-gray-800 text-gray-400']">

              <div class="flex items-center space-x-2">
                <div @click.stop="emit('toggleExpand', node.id)" class="w-4 h-4 flex items-center justify-center hover:bg-white/10 rounded">
                  <svg v-if="node.children.length"
                       :class="['h-2.5 w-2.5 transition-transform duration-200', expandedNodes.has(node.id) ? 'rotate-0' : '-rotate-90']"
                       fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                  </svg>
                  <div v-else class="w-1 h-1 bg-gray-600 rounded-full"></div>
                </div>
                <span class="text-xs font-medium">{{ node.name }}</span>
              </div>

              <button @click.stop="emit('addPage', node)" class="opacity-0 group-hover:opacity-100 text-blue-500 px-1 text-xs">+</button>
            </div>

            <div v-if="node.children.length && expandedNodes.has(node.id)" class="ml-3 border-l border-gray-800 mt-0.5">
              <div v-for="child in node.children" :key="child.id">

                <div @click.stop="emit('update:activePageId', child.id)"
                     :class="['group flex items-center justify-between p-1.5 pl-3 rounded cursor-pointer border border-transparent', 
                              activePageId === child.id ? 'bg-blue-600/15 text-blue-400 border-blue-500/20' : 'hover:bg-gray-800 text-gray-500']">

                  <div class="flex items-center space-x-2">
                    <span @click.stop="emit('toggleExpand', child.id)" v-if="child.children.length" class="text-[8px] opacity-50">
                      {{ expandedNodes.has(child.id) ? '▼' : '▶' }}
                    </span>
                    <div v-else class="w-1 h-[1px] bg-gray-700"></div>
                    <span class="text-xs truncate">{{ child.name }}</span>
                  </div>

                  <button @click.stop="emit('addPage', child)" class="opacity-0 group-hover:opacity-100 text-blue-500 px-1 text-xs">+</button>
                </div>

                <div v-if="child.children.length && expandedNodes.has(child.id)" class="ml-4 border-l border-gray-800/50">
                  <div v-for="gc in child.children" :key="gc.id"
                       @click.stop="emit('update:activePageId', gc.id)"
                       :class="['p-1.5 pl-4 rounded cursor-pointer text-xs truncate italic',
                                activePageId === gc.id ? 'text-blue-400 bg-blue-600/5' : 'text-gray-600 hover:text-gray-400 hover:bg-gray-800']">
                    # {{ gc.name }}
                  </div>
                </div>

              </div>
            </div>

          </div>
        </div>
      </div>
    </div>

    <!-- BOTTOM HALF (ELEMENTS) -->
    <div class="h-1/2 flex flex-col">
      <div class="p-3 bg-[#1a1d23] border-b border-gray-800 shrink-0">
        <h3 class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Forge Elements</h3>
      </div>

      <div class="flex-grow overflow-y-auto p-3 space-y-4 custom-scrollbar">
        <div v-for="(elements, group) in elementGroups" :key="group">
          <p class="text-[9px] font-bold text-gray-600 uppercase mb-2 ml-1">{{ group }}</p>

          <div class="space-y-1.5">
            <div v-for="el in elements"
                 :key="el.type"
                 draggable="true"
                 @dragstart="handleDragStart($event, el)"
                 class="p-2 bg-[#1a1d23] border border-gray-700 rounded text-[11px] cursor-move hover:border-blue-500/50 transition-colors flex items-center space-x-3">

              <span class="text-blue-500 font-bold w-4 text-center">{{ el.icon }}</span>
              <span class="text-gray-400">{{ el.label }}</span>
            </div>
          </div>

        </div>
      </div>
    </div>

  </aside>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #2d3139; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #3b424e; }
</style>