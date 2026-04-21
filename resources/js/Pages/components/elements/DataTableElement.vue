<!-- resources/js/Pages/components/elements/DataTableElement.vue -->
<script setup>
import { ref, computed, reactive } from 'vue';

const props = defineProps({
  element: Object,
  database: Array 
});

const emit = defineEmits(['updateData']);

const showModal = ref(false);
const modalMode = ref('create');
const formData = reactive({});
const editingIndex = ref(-1);

const tablePreviewData = computed(() => {
  const sourceTable = props.element.props.source_table;
  const visibleColumns = props.element.props.columns || [];
  const schema = props.database?.find(t => t.table === sourceTable);
  
  if (!schema) return { headers: ['No Table Selected'], rows: [] };
  
  const headers = visibleColumns.length > 0 ? visibleColumns : schema.columns.map(c => c.name);
  const rows = (schema.records || []).map(record => {
    const row = {};
    headers.forEach(h => row[h] = record[h] ?? '');
    return row;
  });
  return { headers, rows };
});

const openCreateModal = () => {
  const sourceTable = props.element.props.source_table;
  const schema = props.database?.find(t => t.table === sourceTable);
  
  // Safety check: if no table is selected in the Insight panel, don't open the modal
  if (!schema) {
    alert("Please select a Source Table in the Insight panel first.");
    return;
  }

  modalMode.value = 'create';
  const headers = tablePreviewData.value.headers;

  headers.forEach(h => {
    const colSchema = schema.columns?.find(c => c.name === h);
    
    if (colSchema?.type === 'increments') {
      // FIX: Ensure records exists before checking length
      const currentCount = schema.records ? schema.records.length : 0;
      formData[h] = currentCount + 1;
    } else {
      formData[h] = '';
    }
  });

  showModal.value = true;
};

const openEditModal = (row, index) => {
  modalMode.value = 'edit';
  editingIndex.value = index;
  Object.assign(formData, JSON.parse(JSON.stringify(row)));
  showModal.value = true;
};

const saveRecord = () => {
  const schema = props.database?.find(t => t.table === props.element.props.source_table);
  if (!schema) return;
  
  // Initialize records array if it doesn't exist
  if (!schema.records) schema.records = [];

  if (modalMode.value === 'create') {
    schema.records.push({ ...formData });
  } else {
    schema.records[editingIndex.value] = { ...formData };
  }
  
  emit('updateData');
  closeModal();
};

const deleteRecord = (index) => {
  const schema = props.database?.find(t => t.table === props.element.props.source_table);
  if (schema && schema.records && confirm('Are you sure you want to delete this record?')) {
    schema.records.splice(index, 1);
    emit('updateData');
  }
};

const closeModal = () => {
  showModal.value = false;
  Object.keys(formData).forEach(key => delete formData[key]);
};
</script>

<template>
  <div class="w-full p-2">
    <div class="flex justify-between items-center mb-2">
      <h3 class="text-[12px] font-bold uppercase text-gray-700 tracking-tight">
        {{ element.props.source_table || 'No Table' }} Preview
      </h3>
      <button 
        @click="openCreateModal" 
        class="bg-[#ff4400] text-white text-[10px] px-3 py-1 font-bold uppercase rounded hover:opacity-90"
      >
        + Add New
      </button>
    </div>

    <div class="overflow-x-auto bg-white border border-gray-200 rounded shadow-sm">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-200">
            <th v-for="h in tablePreviewData.headers" :key="h" class="p-3 text-[10px] font-bold uppercase text-gray-600 tracking-wider">
              {{ h }}
            </th>
            <th class="p-3 text-[10px] font-bold uppercase text-gray-600 tracking-wider text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, idx) in tablePreviewData.rows" :key="idx" class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
            <td v-for="h in tablePreviewData.headers" :key="h" class="p-3 text-[11px] text-gray-500 font-mono">
              {{ row[h] }}
            </td>
            <td class="p-3 text-right space-x-2">
              <button @click="openEditModal(row, idx)" class="text-blue-500 hover:text-blue-700 text-[10px] font-bold uppercase">Edit</button>
              <button @click="deleteRecord(idx)" class="text-red-400 hover:text-red-600 text-[10px] font-bold uppercase">Del</button>
            </td>
          </tr>
        </tbody>
      </table>
      
      <div v-if="tablePreviewData.rows.length === 0" class="p-8 text-center text-gray-400 text-[10px] uppercase tracking-widest italic">
        No records found in table
      </div>
    </div>

    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-md rounded-lg shadow-2xl overflow-hidden" @click.stop>
          <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
            <span class="text-[12px] font-black uppercase text-gray-800">{{ modalMode }} record</span>
            <button @click="closeModal" class="text-gray-400 hover:text-black">✕</button>
          </div>
          
          <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto">
            <div v-for="h in tablePreviewData.headers" :key="h">
              <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">{{ h }}</label>
              <input 
                v-model="formData[h]" 
                type="text" 
                class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#ff4400] outline-none"
              />
            </div>
          </div>
          
          <div class="p-4 border-t bg-gray-50 flex justify-end gap-2">
            <button @click="closeModal" class="px-4 py-2 text-[10px] font-bold uppercase text-gray-600">Cancel</button>
            <button @click="saveRecord" class="px-4 py-2 bg-[#ff4400] text-white text-[10px] font-bold uppercase rounded">Save Data</button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>