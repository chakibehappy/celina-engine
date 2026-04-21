<script setup>
import { ref, onMounted, reactive, computed } from 'vue'
import axios from 'axios'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  element: Object
})

const items = ref([])
const showModal = ref(false)
const isEditing = ref(false)
const form = reactive({})
const currentId = ref(null)

// ✅ SAFE ACCESS
const table = computed(() => props.element?.props?.source_table || null)
const columns = computed(() => props.element?.props?.columns || [])

// ✅ FETCH (MATCH CONTROLLER)
const fetchData = async () => {
  if (!table.value) return

  try {
    const res = await axios.get(`/api/data/${table.value}`)
    items.value = res.data || []
  } catch (e) {
    console.warn('Fetch failed:', e)
    items.value = []
  }
}

onMounted(fetchData)

// ✅ OPEN MODAL
const openModal = (item = null) => {
  isEditing.value = !!item
  currentId.value = item?.id || null

  columns.value.forEach(col => {
    form[col] = item ? item[col] ?? '' : ''
  })

  showModal.value = true
}

// ✅ CLEAN PAYLOAD (IMPORTANT)
const buildPayload = () => {
  const payload = {}

  columns.value.forEach(col => {
    payload[col] = form[col]
  })

  return payload
}

// ✅ SAVE (MATCH CONTROLLER EXACTLY)
const save = () => {
  if (!table.value) return

  const payload = buildPayload()

  if (isEditing.value) {
    router.put(`/api/data/${table.value}/${currentId.value}`, payload, {
      onSuccess: () => {
        showModal.value = false
        fetchData()
      }
    })
  } else {
    router.post(`/api/data/${table.value}`, payload, {
      onSuccess: () => {
        showModal.value = false
        fetchData()
      }
    })
  }
}

// ✅ DELETE (UNCHANGED, MATCH CONTROLLER)
const deleteItem = (id) => {
  if (!table.value) return

  if (confirm('Delete?')) {
    router.delete(`/api/data/${table.value}/${id}`, {
      onSuccess: fetchData
    })
  }
}
</script>

<template>
  <div class="w-full p-2">

    <!-- HEADER -->
    <div class="flex justify-between items-center mb-2">
      <h3 class="text-[12px] font-bold uppercase text-gray-700 tracking-tight">
        {{ table || 'No Table Selected' }}
      </h3>

      <button 
        @click="openModal()" 
        :disabled="!table"
        class="bg-[#ff4400] disabled:opacity-40 text-white text-[10px] px-3 py-1 font-bold uppercase rounded hover:opacity-90"
      >
        + Add New
      </button>
    </div>

    <!-- TABLE -->
    <div class="overflow-x-auto bg-white border border-gray-200 rounded shadow-sm">
      <table class="w-full text-left border-collapse">

        <thead>
          <tr class="bg-gray-50 border-b border-gray-200">
            <th 
              v-for="col in columns" 
              :key="col"
              class="p-3 text-[10px] font-bold uppercase text-gray-600 tracking-wider"
            >
              {{ col }}
            </th>
            <th class="p-3 text-[10px] font-bold uppercase text-gray-600 text-right">
              Actions
            </th>
          </tr>
        </thead>

        <tbody>
          <tr 
            v-for="item in items" 
            :key="item.id"
            class="border-b border-gray-100 hover:bg-gray-50 transition-colors"
          >
            <td 
              v-for="col in columns" 
              :key="col"
              class="p-3 text-[11px] text-gray-500 font-mono"
            >
              {{ item[col] }}
            </td>

            <td class="p-3 text-right space-x-2">
              <button 
                @click="openModal(item)" 
                class="text-blue-500 text-[10px] font-bold uppercase"
              >
                Edit
              </button>

              <button 
                @click="deleteItem(item.id)" 
                class="text-red-400 text-[10px] font-bold uppercase"
              >
                Del
              </button>
            </td>
          </tr>
        </tbody>

      </table>

      <!-- EMPTY STATE -->
      <div 
        v-if="items.length === 0" 
        class="p-8 text-center text-gray-400 text-[10px] uppercase tracking-widest italic"
      >
        {{ table ? 'No records found' : 'No table selected' }}
      </div>
    </div>

    <!-- MODAL -->
    <div 
      v-if="showModal" 
      class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4"
    >
      <div class="bg-white w-full max-w-md rounded-lg shadow-2xl overflow-hidden">

        <div class="p-4 border-b bg-gray-50 flex justify-between">
          <span class="text-[12px] font-black uppercase">
            {{ isEditing ? 'edit' : 'create' }} record
          </span>
          <button @click="showModal = false">✕</button>
        </div>

        <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto">
          <div v-for="col in columns" :key="col">
            <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">
              {{ col }}
            </label>
            <input 
              v-model="form[col]" 
              class="w-full border px-3 py-2 text-sm focus:ring-2 focus:ring-[#ff4400] outline-none"
            />
          </div>
        </div>

        <div class="p-4 border-t flex justify-end gap-2">
          <button @click="showModal = false" class="text-[10px]">
            Cancel
          </button>

          <button 
            @click="save" 
            class="bg-[#ff4400] text-white px-4 py-2 text-[10px]"
          >
            Save
          </button>
        </div>

      </div>
    </div>

  </div>
</template>