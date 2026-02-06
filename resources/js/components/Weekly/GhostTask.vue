<script setup>
const props = defineProps({
    ghost: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div
        class="relative rounded-lg border border-dashed p-3 opacity-60"
        :style="{ backgroundColor: 'var(--bg-color)', borderColor: 'var(--secondary-text-color)' }"
    >
        <!-- Task content (faded) -->
        <div class="flex items-start gap-3">
            <div
                class="mt-0.5 w-5 h-5 rounded border-2 flex-shrink-0"
                style="border-color: var(--secondary-text-color); opacity: 0.5"
            ></div>
            <span class="flex-1 text-sm line-through" style="color: var(--secondary-text-color)">
                {{ ghost.todo?.content }}
            </span>
        </div>

        <!-- Moved to label -->
        <div class="mt-2 flex items-center gap-1 text-xs" style="color: var(--primary-color)">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
            <span>{{ ghost.moved_to_label }}</span>
        </div>
    </div>
</template>
