<template>
  <MobileTemplateBase
    v-bind="$props"
    surface-class="bg-white text-gray-800 border border-gray-100"
    empty-class="border-gray-200 text-gray-300"
    empty-label="Light canvas"
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
