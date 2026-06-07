<template>
    <div class="fixed top-4 right-4 z-[100] flex flex-col gap-2 pointer-events-none">
        <Transition
            v-for="msg in messages"
            :key="msg.id"
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="translate-x-full opacity-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-to-class="translate-x-full opacity-0"
        >
            <div
                v-if="msg.visible"
                :class="[
                    'pointer-events-auto flex items-start gap-3 px-4 py-3 rounded-lg shadow-lg max-w-sm text-sm',
                    typeClasses[msg.type],
                ]"
            >
                <span class="font-medium">{{ msg.text }}</span>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page     = usePage();
const messages = ref([]);
let counter    = 0;

const typeClasses = {
    success: 'bg-green-50 border border-green-200 text-green-800',
    error:   'bg-red-50 border border-red-200 text-red-800',
    warning: 'bg-yellow-50 border border-yellow-200 text-yellow-800',
};

function push(text, type) {
    if (!text) return;
    const id = ++counter;
    messages.value.push({ id, text, type, visible: true });
    setTimeout(() => {
        const m = messages.value.find(m => m.id === id);
        if (m) m.visible = false;
        setTimeout(() => { messages.value = messages.value.filter(m => m.id !== id); }, 250);
    }, 3500);
}

watch(() => page.props.flash?.success, v => push(v, 'success'));
watch(() => page.props.flash?.error,   v => push(v, 'error'));
watch(() => page.props.flash?.warning, v => push(v, 'warning'));
</script>
