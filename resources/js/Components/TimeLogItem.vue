<script setup>
import { ref } from 'vue';

const props = defineProps({
    log: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['update', 'delete']);

const isEditing = ref(false);
const editLog = ref({ ...props.log });

const startEditing = () => {
    isEditing.value = true;
    editLog.value = { ...props.log };
};

const saveEdit = () => {
    emit('update', props.log.id, {
        start_time: editLog.value.start_time,
        end_time: editLog.value.end_time,
        description: editLog.value.description,
    });
    isEditing.value = false;
};

const cancelEdit = () => {
    isEditing.value = false;
    editLog.value = { ...props.log };
};

const handleDelete = () => {
    if (confirm('ნამდვილად გსურთ წაშლა?')) {
        emit('delete', props.log.id);
    }
};
</script>

<template>
    <div class="group py-2 px-3 rounded-lg bg-sidebar border border-sidebar-border hover:border-primary transition-colors">
        <!-- Display Mode -->
        <div v-if="!isEditing" class="flex items-center gap-3">
            <span v-if="log.task_id" class="inline-block min-w-[100px] px-1.5 py-0.5 bg-gray-100 text-gray-600 text-xs rounded font-mono whitespace-nowrap text-center">
                {{ log.task_id }}
            </span>
            <span v-else class="inline-block min-w-[100px]"></span>
            <div class="flex items-center gap-1 text-[#346ec9] font-medium text-sm whitespace-nowrap">
                <span>{{ log.start_time }}</span>
                <span class="text-[#9ca3af]">→</span>
                <span>{{ log.end_time || '--:--' }}</span>
            </div>
            <div class="px-2 py-0.5 bg-[#346ec9]/10 text-[#346ec9] text-xs rounded font-medium whitespace-nowrap">
                {{ log.formatted_duration }}
            </div>
            <p class="flex-1 text-[#1f2937] text-sm truncate">{{ log.description || '' }}</p>
            <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                <button
                    @click="startEditing"
                    class="p-1 text-[#9ca3af] hover:text-[#346ec9] transition-colors"
                    title="რედაქტირება"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </button>
                <button
                    @click="handleDelete"
                    class="p-1 text-[#9ca3af] hover:text-red-500 transition-colors"
                    title="წაშლა"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Edit Mode -->
        <div v-else class="flex items-center gap-2">
            <input
                v-model="editLog.start_time"
                type="time"
                class="w-24 px-2 py-1 text-sm border border-[#346ec9] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#346ec9] text-[#1f2937]"
            />
            <input
                v-model="editLog.end_time"
                type="time"
                class="w-24 px-2 py-1 text-sm border border-[#346ec9] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#346ec9] text-[#1f2937]"
            />
            <input
                v-model="editLog.description"
                type="text"
                placeholder="აღწერა"
                class="flex-1 px-2 py-1 text-sm border border-[#346ec9] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#346ec9] text-[#1f2937]"
            />
            <button
                @click="saveEdit"
                class="px-3 py-1 bg-[#346ec9] text-white rounded-lg hover:bg-[#2d5eb0] text-sm"
            >
                შენახვა
            </button>
            <button
                @click="cancelEdit"
                class="px-3 py-1 bg-[#e5e7eb] text-[#1f2937] rounded-lg hover:bg-[#d1d5db] text-sm"
            >
                გაუქმება
            </button>
        </div>
    </div>
</template>
