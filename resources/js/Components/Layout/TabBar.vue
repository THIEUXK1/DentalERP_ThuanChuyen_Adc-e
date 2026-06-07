<template>
    <div v-if="tabs.length > 0" class="relative flex items-stretch bg-gray-100 border-b border-gray-200 select-none">
        <!-- Scroll-left button -->
        <button v-if="canScrollLeft" type="button" @click="scroll(-160)"
            class="flex-shrink-0 flex items-center justify-center w-7 text-gray-400 hover:text-gray-700 hover:bg-gray-200 border-r border-gray-200 transition-colors"
            aria-label="Cuộn trái">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
            </svg>
        </button>

        <!-- Tab list -->
        <div ref="scrollEl" role="tablist" class="flex flex-1 overflow-x-auto hide-scrollbar" @scroll="checkScroll">
            <Link
                v-for="tab in tabs"
                :key="tab.url"
                :href="tab.url"
                role="tab"
                :aria-selected="tab.active"
                :class="[
                    'group relative flex items-center gap-1.5 px-3.5 py-2 text-xs whitespace-nowrap',
                    'border-b-2 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-inset',
                    tab.active
                        ? 'border-primary-600 text-primary-700 bg-white font-medium shadow-sm'
                        : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-white/70',
                ]"
            >
                <span>{{ tab.title }}</span>
                <button
                    type="button"
                    @click.prevent.stop="closeTab(tab.url)"
                    :aria-label="`Đóng ${tab.title}`"
                    class="opacity-0 group-hover:opacity-100 rounded p-0.5 transition-all hover:bg-gray-200 text-gray-400 hover:text-gray-700"
                >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </Link>
        </div>

        <!-- Scroll-right button -->
        <button v-if="canScrollRight" type="button" @click="scroll(160)"
            class="flex-shrink-0 flex items-center justify-center w-7 text-gray-400 hover:text-gray-700 hover:bg-gray-200 border-l border-gray-200 transition-colors"
            aria-label="Cuộn phải">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, nextTick } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useTabs } from '@/composables/useTabs';

const { tabs, closeTab } = useTabs();

const scrollEl       = ref(null);
const canScrollLeft  = ref(false);
const canScrollRight = ref(false);

function checkScroll() {
    if (!scrollEl.value) return;
    const { scrollLeft, scrollWidth, clientWidth } = scrollEl.value;
    canScrollLeft.value  = scrollLeft > 2;
    canScrollRight.value = scrollLeft + clientWidth < scrollWidth - 2;
}

function scroll(delta) {
    scrollEl.value?.scrollBy({ left: delta, behavior: 'smooth' });
}

// Scroll active tab into view whenever tabs change
watch(tabs, async () => {
    await nextTick();
    checkScroll();
    const active = scrollEl.value?.querySelector('[aria-selected="true"]');
    active?.scrollIntoView({ block: 'nearest', inline: 'nearest' });
}, { deep: true });

onMounted(() => nextTick().then(checkScroll));
</script>

<style scoped>
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
