<script setup>
import { ref, computed, reactive, watch } from 'vue';

const props = defineProps({
  element: Object,
  database: Array 
});

const emit = defineEmits(['updateData']);

// --- UI & Form State ---
const showModal = ref(false);
const modalMode = ref('create');
const formData = reactive({});
const editingOriginalIndex = ref(-1);

// --- Search, Sort & Pagination State ---
const searchQuery = ref('');
const currentPage = ref(1);
const rowsPerPage = ref(10);
const sortConfig = reactive({
  key: null,
  direction: 'asc' // 'asc' or 'desc'
});

// Reset pagination on search or sort change
watch([searchQuery, () => sortConfig.key, () => sortConfig.direction], () => {
  currentPage.value = 1;
});

const handleSort = (key) => {
  if (sortConfig.key === key) {
    sortConfig.direction = sortConfig.direction === 'asc' ? 'desc' : 'asc';
  } else {
    sortConfig.key = key;
    sortConfig.direction = 'asc';
  }
};

const tablePreviewData = computed(() => {
  const sourceTable = props.element.props.source_table;
  const visibleColumns = props.element.props.columns || [];
  const schema = props.database?.find(t => t.table === sourceTable);
  
  if (!schema) return { headers: ['No Table Selected'], rows: [], totalRows: 0 };
  
  const headers = visibleColumns.length > 0 ? visibleColumns : schema.columns.map(c => c.name);
  
  // 1. Map with Original Index
  let processedRows = (schema.records || []).map((record, index) => ({
    ...record,
    __originalIndex: index
  }));

  // 2. Filter (Search)
  if (searchQuery.value) {
    const search = searchQuery.value.toLowerCase();
    processedRows = processedRows.filter(row => 
      headers.some(h => String(row[h] || '').toLowerCase().includes(search))
    );
  }

  // 3. Sort
  if (sortConfig.key) {
    processedRows.sort((a, b) => {
      const valA = a[sortConfig.key];
      const valB = b[sortConfig.key];
      
      if (valA === valB) return 0;
      
      const modifier = sortConfig.direction === 'asc' ? 1 : -1;
      
      // Basic type handling (numbers vs strings)
      if (typeof valA === 'number' && typeof valB === 'number') {
        return (valA - valB) * modifier;
      }
      return String(valA).localeCompare(String(valB)) * modifier;
    });
  }

  // 4. Paginate
  const totalRows = processedRows.length;
  const start = (currentPage.value - 1) * rowsPerPage.value;
  const pagedRows = processedRows.slice(start, start + rowsPerPage.value);

  return { 
    headers, 
    rows: pagedRows, 
    totalRows,
    totalPages: Math.ceil(totalRows / rowsPerPage.value)
  };
});

// --- Actions (Save/Delete/Modal logic remains same) ---
const openCreateModal = () => {
  const schema = props.database?.find(t => t.table === props.element.props.source_table);
  if (!schema) return alert("Select a table first.");
  modalMode.value = 'create';
  tablePreviewData.value.headers.forEach(h => {
    const col = schema.columns?.find(c => c.name === h);
    formData[h] = col?.type === 'increments' ? (schema.records?.length || 0) + 1 : '';
  });
  showModal.value = true;
};

const openEditModal = (row) => {
  modalMode.value = 'edit';
  editingOriginalIndex.value = row.__originalIndex;
  const cleanRow = { ...row };
  delete cleanRow.__originalIndex;
  Object.assign(formData, JSON.parse(JSON.stringify(cleanRow)));
  showModal.value = true;
};

const saveRecord = () => {
  const schema = props.database?.find(t => t.table === props.element.props.source_table);
  if (!schema) return;
  if (!schema.records) schema.records = [];
  if (modalMode.value === 'create') schema.records.push({ ...formData });
  else schema.records[editingOriginalIndex.value] = { ...formData };
  emit('updateData');
  closeModal();
};

const deleteRecord = (row) => {
  const schema = props.database?.find(t => t.table === props.element.props.source_table);
  if (schema && confirm('Delete record?')) {
    schema.records.splice(row.__originalIndex, 1);
    emit('updateData');
  }
};

const closeModal = () => {
  showModal.value = false;
  Object.keys(formData).forEach(k => delete formData[k]);
};
</script>

<template>
  <div class="w-full p-2">
    <div class="flex justify-between items-center mb-4">
      <div class="flex flex-col">
        <h3 class="text-[12px] font-bold uppercase text-gray-700 tracking-tight">
          {{ element.props.source_table || 'No Table' }} Preview
        </h3>
        <span class="text-[9px] text-gray-400 font-mono">{{ tablePreviewData.totalRows }} total records</span>
      </div>
      <button @click="openCreateModal" class="bg-[#ff4400] text-white text-[10px] px-3 py-1.5 font-bold uppercase rounded hover:brightness-110 transition-all">
        + Add New
      </button>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
      <div class="p-3 border-b border-gray-100 bg-gray-50/50">
        <div class="relative group">
          <div class="absolute left-2.5 top-2.5 text-gray-400 group-focus-within:text-[#ff4400]">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          </div>
          <input 
            v-model="searchQuery"
            type="text" 
            placeholder="Search database..." 
            class="w-full pl-9 pr-4 py-2 text-[11px] border border-gray-200 rounded bg-white focus:outline-none focus:ring-1 focus:ring-[#ff4400] focus:border-[#ff4400] transition-all"
          />
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50/80 border-b border-gray-200">
              <th 
                v-for="h in tablePreviewData.headers" 
                :key="h" 
                @click="handleSort(h)"
                class="p-3 text-[10px] font-black uppercase text-gray-500 tracking-wider cursor-pointer hover:bg-gray-100 transition-colors group"
              >
                <div class="flex items-center gap-1">
                  {{ h }}
                  <span class="text-[8px] opacity-0 group-hover:opacity-100 transition-opacity" :class="{'opacity-100 text-[#ff4400]': sortConfig.key === h}">
                    {{ sortConfig.key === h ? (sortConfig.direction === 'asc' ? '▲' : '▼') : '↕' }}
                  </span>
                </div>
              </th>
              <th class="p-3 text-[10px] font-bold uppercase text-gray-500 tracking-wider text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="(row, idx) in tablePreviewData.rows" :key="idx" class="hover:bg-gray-50/50 transition-colors">
              <td v-for="h in tablePreviewData.headers" :key="h" class="p-3 text-[11px] text-gray-600 font-mono truncate max-w-[180px]">
                {{ row[h] }}
              </td>
              <td class="p-3 text-right space-x-3">
                <button @click="openEditModal(row)" class="text-[#ff4400] hover:underline text-[9px] font-black uppercase">Edit</button>
                <button @click="deleteRecord(row)" class="text-gray-300 hover:text-red-600 text-[9px] font-black uppercase transition-colors">Del</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="tablePreviewData.rows.length === 0" class="p-12 text-center text-gray-400 text-[10px] uppercase tracking-widest italic">
        Empty result set
      </div>

      <div class="p-3 border-t border-gray-100 bg-gray-50 flex items-center justify-between">
        <div class="text-[9px] text-gray-400 uppercase font-black tracking-tighter">
          PAGE {{ currentPage }} / {{ tablePreviewData.totalPages || 1 }}
        </div>
        <div class="flex gap-1">
          <button @click="currentPage--" :disabled="currentPage === 1" class="px-3 py-1 text-[10px] font-bold border rounded bg-white text-gray-500 disabled:opacity-20 hover:bg-gray-50 transition-all">PREV</button>
          <button @click="currentPage++" :disabled="currentPage >= tablePreviewData.totalPages" class="px-3 py-1 text-[10px] font-bold border rounded bg-white text-gray-500 disabled:opacity-20 hover:bg-gray-50 transition-all">NEXT</button>
        </div>
      </div>
    </div>

    <Teleport to="body">
       <div v-if="showModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-md rounded-lg shadow-2xl overflow-hidden" @click.stop>
          <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
            <span class="text-[11px] font-black uppercase text-gray-800 tracking-widest">{{ modalMode }} entry</span>
            <button @click="closeModal" class="text-gray-400 hover:text-black">✕</button>
          </div>
          <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto">
            <div v-for="h in tablePreviewData.headers" :key="h">
              <label class="block text-[9px] font-black uppercase text-gray-400 mb-1 tracking-tighter">{{ h }}</label>
              <input v-model="formData[h]" type="text" class="w-full border border-gray-200 rounded px-3 py-2 text-sm focus:ring-1 focus:ring-[#ff4400] outline-none" />
            </div>
          </div>
          <div class="p-4 border-t bg-gray-50 flex justify-end gap-2">
            <button @click="closeModal" class="px-4 py-2 text-[10px] font-bold uppercase text-gray-400 hover:text-black">Cancel</button>
            <button @click="saveRecord" class="px-6 py-2 bg-[#ff4400] text-white text-[10px] font-black uppercase rounded shadow-lg">Commit Change</button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>