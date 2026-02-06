<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        required: true,
    },
});

const emit = defineEmits(['close', 'save']);

const formData = ref({
    start_time: '',
    end_time: '',
    description: '',
});

const handleSave = () => {
    if (formData.value.start_time) {
        emit('save', { ...formData.value });
        formData.value = {
            start_time: '',
            end_time: '',
            description: '',
        };
    }
};

const handleClose = () => {
    formData.value = {
        start_time: '',
        end_time: '',
        description: '',
    };
    emit('close');
};

const handleKeydown = (e) => {
    if (e.key === 'Escape') {
        handleClose();
    }
};

watch(() => props.show, (newVal) => {
    if (newVal) {
        setTimeout(() => {
            document.getElementById('start-time-input')?.focus();
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
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden border border-[#e5e7eb]">
                <div class="bg-[#1f2937] px-6 py-4 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-white">ახალი ჩანაწერი</h3>
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
                    <div class="space-y-4 mb-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-[#1f2937] mb-2">დაწყება</label>
                                <input
                                    id="start-time-input"
                                    v-model="formData.start_time"
                                    @keydown="handleKeydown"
                                    type="time"
                                    class="w-full px-4 py-2 border border-[#e5e7eb] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#346ec9] focus:border-[#346ec9] transition-colors text-[#1f2937]"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-[#1f2937] mb-2">დასრულება</label>
                                <input
                                    v-model="formData.end_time"
                                    @keydown="handleKeydown"
                                    type="time"
                                    class="w-full px-4 py-2 border border-[#e5e7eb] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#346ec9] focus:border-[#346ec9] transition-colors text-[#1f2937]"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-[#1f2937] mb-2">აღწერა</label>
                            <textarea
                                v-model="formData.description"
                                @keydown="handleKeydown"
                                rows="3"
                                placeholder="რაზე მუშაობდი?"
                                class="w-full px-4 py-2 border border-[#e5e7eb] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#346ec9] focus:border-[#346ec9] transition-colors resize-none text-[#1f2937]"
                            ></textarea>
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
                    </div>

                    <div class="mt-4 text-xs text-[#9ca3af] text-center">
                        <kbd class="px-2 py-1 bg-[#e5e7eb] rounded text-[#1f2937]">Esc</kbd> გაუქმება
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
