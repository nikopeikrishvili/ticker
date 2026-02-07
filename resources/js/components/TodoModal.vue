<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        required: true,
    },
});

const emit = defineEmits(['close', 'save', 'saveAndStart']);

const content = ref('');
const priority = ref(3);

const priorityOptions = [
    { value: 1, label: 'ძალიან დაბალი', color: 'bg-gray-300' },
    { value: 2, label: 'დაბალი', color: 'bg-gray-400' },
    { value: 3, label: 'საშუალო', color: 'bg-gray-400' },
    { value: 4, label: 'მაღალი', color: 'bg-orange-500' },
    { value: 5, label: 'ძალიან მაღალი', color: 'bg-red-500' },
];

const handleSave = () => {
    if (content.value.trim()) {
        emit('save', { content: content.value, priority: priority.value });
        content.value = '';
        priority.value = 3;
    }
};

const handleSaveAndStart = () => {
    if (content.value.trim()) {
        emit('saveAndStart', { content: content.value, priority: priority.value });
        content.value = '';
        priority.value = 3;
    }
};

const handleClose = () => {
    content.value = '';
    priority.value = 3;
    emit('close');
};

const handleKeydown = (e) => {
    if (e.key === 'Escape') {
        handleClose();
    } else if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        handleSave();
    }
};

watch(() => props.show, (newVal) => {
    if (newVal) {
        setTimeout(() => {
            document.getElementById('todo-input')?.focus();
        }, 100);
    }
});
</script>

<template>
    <Transition name="modal">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="handleClose">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-[#1f2937]/50"></div>

            <!-- Modal -->
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden border border-[#e5e7eb]">
                <div class="bg-[#1f2937] px-6 py-4 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-white">ახალი დავალება</h3>
                    <button
                        @click="handleClose"
                        class="text-white/60 hover:text-white transition-colors"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-[#1f2937] mb-2">დავალების აღწერა</label>
                        <textarea
                            id="todo-input"
                            v-model="content"
                            @keydown="handleKeydown"
                            rows="4"
                            placeholder="რა უნდა გააკეთო?"
                            class="w-full px-4 py-2 border border-[#e5e7eb] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#346ec9] focus:border-[#346ec9] transition-colors resize-none text-[#1f2937]"
                        ></textarea>
                    </div>

                    <!-- Priority Selector -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-[#1f2937] mb-2">პრიორიტეტი</label>
                        <div class="flex gap-2">
                            <button
                                v-for="option in priorityOptions"
                                :key="option.value"
                                @click="priority = option.value"
                                type="button"
                                class="flex-1 py-2 px-1 rounded-lg border-2 transition-all flex flex-col items-center gap-1.5"
                                :class="priority === option.value
                                    ? 'border-[#346ec9] bg-[#346ec9]/5'
                                    : 'border-transparent bg-[#f3f4f6] hover:bg-[#e5e7eb]'"
                            >
                                <!-- Dots -->
                                <div class="flex gap-px">
                                    <span
                                        v-for="i in 5"
                                        :key="i"
                                        class="w-1.5 h-1.5 rounded-full"
                                        :class="i <= option.value ? option.color : 'bg-gray-200'"
                                    ></span>
                                </div>
                                <span class="text-[10px] text-[#6b7280] font-medium whitespace-nowrap">{{ option.label }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="flex gap-3 justify-end">
                        <button
                            @click="handleClose"
                            class="px-4 py-2 text-[#1f2937] hover:bg-[#e5e7eb] rounded-lg transition-colors font-medium"
                        >
                            გაუქმება
                        </button>
                        <button
                            @click="handleSave"
                            class="px-4 py-2 bg-[#346ec9] text-white rounded-lg hover:bg-[#2d5eb0] transition-colors font-medium"
                        >
                            დამატება
                        </button>
                        <button
                            @click="handleSaveAndStart"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium flex items-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z" />
                            </svg>
                            დამატება და დაწყება
                        </button>
                    </div>

                    <div class="mt-4 text-xs text-[#9ca3af] text-center">
                        <kbd class="px-2 py-1 bg-[#e5e7eb] rounded text-[#1f2937]">Enter</kbd> შენახვა | <kbd class="px-2 py-1 bg-[#e5e7eb] rounded text-[#1f2937]">Esc</kbd> გაუქმება
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-active .relative,
.modal-leave-active .relative {
    transition: transform 0.2s ease;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
    transform: scale(0.95);
}

kbd {
    font-family: monospace;
    font-size: 0.75rem;
}
</style>
