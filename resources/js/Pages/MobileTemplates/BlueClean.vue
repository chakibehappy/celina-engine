<template>
  <MobileTemplateBase
    v-bind="$props"
    surface-class="bg-[#f8fafc] text-slate-800"
    empty-class="border-blue-100 text-blue-200"
    empty-label="Canvas ready"
    @selectElement="$emit('selectElement', $event)"
    @recordHistory="$emit('recordHistory')"
    @updateData="$emit('updateData')"
  />
</template>

<script setup>
import MobileTemplateBase from '../components/MobileTemplateBase.vue';

defineProps(['activePage', 'selectedElement', 'database', 'mode']);
defineEmits(['selectElement', 'recordHistory', 'updateData']);
</script>
