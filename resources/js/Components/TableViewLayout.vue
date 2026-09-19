<script setup lang="ts">
defineProps<{ editorOpen?: boolean }>();
</script>

<template>
    <div class="users-workspace" :class="{ 'has-editor': editorOpen }">
        <slot />
    </div>
</template>
