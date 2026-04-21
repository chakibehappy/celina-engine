<script setup>
import { computed } from 'vue';
// ✅ Added database to props
const props = defineProps(['activePage', 'activePageId', 'selectedElement', 'uiConfig', 'database']);
const emit = defineEmits(['updateStyle']);

const selectedElementData = computed(() => {
  if (!props.selectedElement || !props.activePage) return null;
  const findInTree = (list) => {
    for (let el of list) {
      if (el.id === props.selectedElement) return el;
      if (el.children) {
        const found = findInTree(el.children);
        if (found) return found;
      }
    }
    return null;
  };
  return findInTree(props.activePage.elements || []);
});

// ✅ Data Table Logic: Find schema and toggle columns
const currentTableSchema = computed(() => {
  const tableName = selectedElementData.value?.props?.source_table;
  if (!tableName || !props.database) return null;
  return props.database.find(t => t.table === tableName);
});

const toggleColumn = (colName) => {
  if (!selectedElementData.value.props.columns) selectedElementData.value.props.columns = [];
  const idx = selectedElementData.value.props.columns.indexOf(colName);
  if (idx > -1) selectedElementData.value.props.columns.splice(idx, 1);
  else selectedElementData.value.props.columns.push(colName);
};

/**
 * STRATEGY: Computed with get/set to handle the 'px' suffix 
 * while keeping the input as a clean number.
 */
const styleRef = (key) => {
  return computed({
    get: () => {
      const val = selectedElementData.value?.props?.style?.[key] || '';
      return typeof val === 'string' ? val.replace('px', '') : val;
    },
    set: (newValue) => {
      const finalValue = newValue === '' || newValue === null ? '' : newValue + 'px';
      emit('updateStyle', key, finalValue);
    }
  });
};

// Style mapping
const width = styleRef('width');
const height = styleRef('height');
const padding = styleRef('padding');
const margin = styleRef('margin');
const gap = styleRef('gap');
const fontSize = styleRef('fontSize');
</script>

<template>
  <aside class="w-64 border-l border-gray-800 bg-[#14171c] flex flex-col z-20">
    <div class="p-3 bg-[#1a1d23] border-b border-gray-800 shrink-0 flex justify-between items-center">
      <h3 class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Insight</h3>
      <span v-if="selectedElementData" class="text-[9px] text-blue-400 font-mono">{{ selectedElementData.type }}</span>
    </div>
    
    <div class="flex-grow overflow-y-auto p-4 custom-scrollbar">
      <div v-if="activePage" class="space-y-6">
        
        <div>
          <label class="text-[9px] text-gray-600 uppercase font-bold mb-2 block tracking-tighter">Object Properties</label>
          <div class="space-y-3">
            <div>
              <div class="text-[10px] text-gray-500 mb-1">Display Name</div>
              <input v-model="activePage.name" class="w-full bg-[#0d0f14] border border-gray-800 p-2 rounded text-xs focus:border-blue-500 outline-none" />
            </div>
            
            <div>
              <div class="text-[10px] text-gray-500 mb-1">Route Slug</div>
              <input v-model="activePage.slug" class="w-full bg-[#0d0f14] border border-gray-800 p-2 rounded text-[10px] font-mono text-gray-400" readonly />
            </div>

            <div class="pt-2 border-t border-gray-800/50 mt-4">
                <div class="flex items-center justify-between mb-3"> 
                    <label class="text-[10px] text-gray-500">Render Page Canvas</label>
                    <button @click="activePage.showPage = !activePage.showPage" 
                        :class="['w-8 h-4 rounded-full transition-colors relative', activePage.showPage ? 'bg-blue-600' : 'bg-gray-700']">
                        <div :class="['w-3 h-3 bg-white rounded-full absolute top-0.5 transition-all', activePage.showPage ? 'right-0.5' : 'left-0.5']"></div>
                    </button>
                </div>
                
                <div v-if="!activePage.showPage">
                    <div class="text-[10px] text-gray-500 mb-1">Menu Action</div>
                    <select v-model="activePage.menuAction" class="w-full bg-[#0d0f14] border border-gray-800 p-2 rounded text-[10px] outline-none text-gray-400 focus:border-blue-500">
                        <option v-for="act in ['None', 'Logout', 'Print', 'Custom']" :key="act" :value="act">{{ act }}</option>
                    </select>
                </div>
            </div>
          </div>
        </div>

        <div v-if="selectedElement" class="pt-4 border-t border-gray-800/50 space-y-4">
          
          <div v-if="selectedElementData?.type === 'data-table'" class="space-y-3 p-2 bg-purple-500/5 border border-purple-500/10 rounded">
             <div class="text-[9px] text-purple-400/80 font-bold uppercase tracking-tighter">Database Connector</div>
             <div>
                <div class="text-[9px] text-gray-500 mb-1">Source Table</div>
                <select v-model="selectedElementData.props.source_table" class="style-input" @change="selectedElementData.props.columns = []">
                  <option value="">None</option>
                  <option v-for="t in database" :key="t.table" :value="t.table">{{ t.table }}</option>
                </select>
             </div>
             
             <div v-if="currentTableSchema" class="space-y-1 mt-2">
                <div class="text-[8px] text-gray-600 uppercase font-bold">Visible Columns</div>
                <div v-for="col in currentTableSchema.columns" :key="col.name" 
                     @click="toggleColumn(col.name)"
                     class="flex items-center gap-2 p-1 px-2 rounded bg-black/20 border border-gray-800 cursor-pointer hover:border-gray-600">
                  <div class="w-2 h-2 rounded-sm border border-gray-600" :class="selectedElementData.props.columns?.includes(col.name) ? 'bg-blue-500 border-blue-400' : ''"></div>
                  <span class="text-[10px] font-mono text-gray-400">{{ col.name }}</span>
                </div>
             </div>
          </div>

          <label class="text-[9px] text-gray-600 uppercase font-bold block">Element Styling</label>
          
          <div class="grid grid-cols-2 gap-2">
            <div>
              <div class="text-[9px] text-gray-500 mb-1">Width (px)</div>
              <input v-model="width" type="number" placeholder="auto" class="style-input" />
            </div>
            <div>
              <div class="text-[9px] text-gray-500 mb-1">Height (px)</div>
              <input v-model="height" type="number" placeholder="auto" class="style-input" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-2">
            <div>
              <div class="text-[9px] text-gray-500 mb-1">Padding</div>
              <input v-model="padding" type="number" class="style-input" />
            </div>
            <div>
              <div class="text-[9px] text-gray-500 mb-1">Margin</div>
              <input v-model="margin" type="number" class="style-input" />
            </div>
          </div>

          <div v-if="selectedElementData?.type.includes('container')" class="space-y-3 p-2 bg-blue-500/5 border border-blue-500/10 rounded">
             <div class="text-[9px] text-blue-400/80 font-bold uppercase">Container Config</div>
             <div>
                <div class="text-[9px] text-gray-500 mb-1">Gap</div>
                <input v-model="gap" type="number" class="style-input" />
             </div>
          </div>

          <div v-if="selectedElementData?.type === 'text'" class="space-y-3 p-2 bg-orange-500/5 border border-orange-500/10 rounded">
             <div class="text-[9px] text-orange-400/80 font-bold uppercase">Text Config</div>
             <div class="grid grid-cols-2 gap-2">
                <div>
                  <div class="text-[9px] text-gray-500 mb-1">Size</div>
                  <input v-model="fontSize" type="number" class="style-input" />
                </div>
                <div>
                  <div class="text-[9px] text-gray-500 mb-1">Color</div>
                  <input type="color" :value="selectedElementData.props.style?.color || '#000000'" @input="e => emit('updateStyle', 'color', e.target.value)" class="color-input" />
                </div>
             </div>
          </div>

          <div>
              <div class="text-[10px] text-gray-500 mb-1">Background Color</div>
              <input type="color" :value="selectedElementData.props.style?.backgroundColor || '#ffffff'" @input="e => emit('updateStyle', 'backgroundColor', e.target.value)" class="color-input h-8" />
          </div>
        </div>

        <div class="pt-4 border-t border-gray-800/50">
           <label class="text-[9px] text-gray-600 uppercase font-bold mb-2 block">Node Status</label>
           <div class="p-2 bg-[#1a1d23] rounded border border-gray-800 mb-2">
              <div class="text-[8px] text-gray-500 uppercase">Selected Element ID</div>
              <div class="text-[10px] font-mono text-blue-400 break-all select-all">{{ selectedElement || 'None' }}</div>
           </div>
        </div>
      </div>
    </div>
  </aside>
</template>

<style scoped>
.style-input {
  width: 100%;
  background-color: #0d0f14;
  border: 1px solid #1f2937;
  padding: 6px;
  border-radius: 4px;
  font-size: 10px;
  outline: none;
  color: #d1d5db;
}

.style-input::-webkit-outer-spin-button,
.style-input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.color-input {
  width: 100%;
  height: 28px;
  background: transparent;
  border: 1px solid #1f2937;
  border-radius: 4px;
  cursor: pointer;
}

.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #2d3139; border-radius: 10px; }
</style>