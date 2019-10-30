<template>
    <span v-if="badge.label" class="label" :class="badge.cssclass || 'label-default'" :title="badge.label">{{ badge.label }}</span>
</template>

<script>
    export default {
        name: "Labels",
        props: {
            badge: Object
        }
    }
</script>

<style scoped>
    .label{
        margin-right: 4px;
    }
</style>
