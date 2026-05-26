<template>
  <MobileTemplateBase
    v-bind="$props"
    surface-class="bg-[#0f172a] text-slate-100"
    empty-class="border-slate-700 text-slate-500 hover:border-indigo-500/50"
    empty-label="Dark canvas — add modules"
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
