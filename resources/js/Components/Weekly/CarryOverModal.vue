<script setup>
import { ref } from 'vue';
import { useWeeklyPlanner } from '@/Composables/useWeeklyPlanner';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'carried-over']);

const { carryOverToNextWeek } = useWeeklyPlanner();

const isLoading = ref(false);
const result = ref(null);

const handleCarryOver = async () => {
    isLoading.value = true;
    result.value = null;

    try {
        const response = await carryOverToNextWeek();
        result.value = response;
        emit('carried-over', response);
    } catch (err) {
        console.error('Failed to carry over:', err);
    } finally {
        isLoading.value = false;
    }
};

const handleClose = () => {
    result.value = null;
    emit('close');
};
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center"
            >
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-[#1f2937]/50"
                    @click="handleClose"
                ></div>

                <!-- Modal -->
                <div class="relative bg-background rounded-xl shadow-xl max-w-md w-full mx-4 overflow-hidden border border-border">
                    <!-- Header -->
                    <div class="bg-[#1f2937] px-6 py-4">
                        <h2 class="text-lg font-bold text-white">
                            შემდეგ კვირაზე გადატანა
                        </h2>
                    </div>

                    <!-- Content -->
                    <div class="p-6">
                        <template v-if="!result">
                            <p class="text-foreground mb-6">
                                გსურთ ყველა დაუსრულებელი დავალების შემდეგ კვირაზე გადატანა?
                            </p>

                            <div class="bg-muted/50 border border-border rounded-lg p-4 mb-6">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-primary mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div class="text-sm text-foreground">
                                        <p class="font-medium">ყურადღება</p>
                                        <p class="text-muted-foreground">გადატანილი დავალებები დარჩება იმავე დღეს, რომელ დღესაც იყო დანიშნული მიმდინარე კვირაში.</p>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template v-else>
                            <div class="text-center py-4">
                                <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <p class="text-lg font-medium text-foreground mb-2">
                                    გადატანილია {{ result.movedCount }} დავალება
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    ახლა ხედავთ შემდეგი კვირის დაგეგმვას
                                </p>
                            </div>
                        </template>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 bg-muted/30 flex justify-end gap-3 border-t border-border">
                        <template v-if="!result">
                            <button
                                @click="handleClose"
                                class="px-4 py-2 text-foreground hover:bg-muted rounded-lg transition-colors"
                                :disabled="isLoading"
                            >
                                გაუქმება
                            </button>
                            <button
                                @click="handleCarryOver"
                                class="px-4 py-2 bg-primary text-primary-foreground rounded-lg transition-colors flex items-center gap-2"
                                :disabled="isLoading"
                            >
                                <svg v-if="isLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>გადატანა</span>
                            </button>
                        </template>
                        <template v-else>
                            <button
                                @click="handleClose"
                                class="px-4 py-2 bg-primary text-primary-foreground rounded-lg transition-colors"
                            >
                                დახურვა
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
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
</style>
