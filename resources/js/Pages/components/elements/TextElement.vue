<!-- resources/js/Pages/components/elements/TextElement.vue -->
<script setup>
const props = defineProps({ element: Object });
const emit = defineEmits(['updateTree']);

const updateContent = (e) => {
  props.element.props.content = e.target.innerText;
  emit('updateTree');
};
</script>

<template>
  <div class="w-full">
    <p 
      class="outline-none" 
      contenteditable="true"
      @blur="updateContent"
      :style="{
        fontSize: element.props.style?.fontSize,
        color: element.props.style?.color,
        fontWeight: element.props.style?.fontWeight
      }"
    >
      {{ element.props.content || 'Click to edit text' }}
    </p>
  </div>
</template>