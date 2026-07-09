<template>
    <div v-if="tabs.length > 0" id="tabbar-root" class="relative flex items-stretch bg-gray-100 border-b border-gray-200 select-none">

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
            <a
                v-for="(tab, index) in tabs"
                :key="tab.url"
                :href="tab.url"
                role="tab"
                :aria-selected="isActive(tab)"
                draggable="true"
                :class="[
                    'group relative flex items-center gap-1.5 px-3 py-2 text-xs whitespace-nowrap cursor-pointer',
                    'border-t-2 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-inset',
                    isActive(tab)
                        ? 'border-t-primary-600 bg-white text-primary-700 font-semibold -mb-px border-b border-b-white'
                        : 'border-t-transparent text-gray-500 hover:text-gray-800 hover:bg-white/70',
                    dragOverIndex === index ? 'bg-primary-50' : '',
                    dragIndex === index ? 'opacity-40' : '',
                ]"
                @click.prevent="clickTab(tab)"
                @contextmenu.prevent="openContext($event, tab)"
                @dragstart="onDragStart($event, index)"
                @dragover.prevent="onDragOver(index)"
                @dragleave="onDragLeave(index)"
                @drop.prevent="onDrop(index)"
                @dragend="onDragEnd"
            >
                <!-- Pin icon -->
                <svg v-if="tab.pinned" class="w-3 h-3 flex-shrink-0 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M16 1v6l2 3v2H14v5l-1 4-1-4v-5H8v-2l2-3V1h6zm-3 0H11v5.5L9 9v1h6V9l-2-2.5V1z"/>
                </svg>

                <span>{{ tab.title }}</span>

                <!-- Close button (hidden for pinned tabs on hover — still closable by right-click) -->
                <button
                    type="button"
                    @click.prevent.stop="closeTab(tab.url)"
                    :aria-label="`Đóng ${tab.title}`"
                    :class="[
                        'rounded p-0.5 transition-all hover:bg-gray-200 text-gray-400 hover:text-gray-700',
                        tab.pinned ? 'hidden' : 'opacity-0 group-hover:opacity-100',
                    ]"
                >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </a>
        </div>

        <!-- Scroll-right button -->
        <button v-if="canScrollRight" type="button" @click="scroll(160)"
            class="flex-shrink-0 flex items-center justify-center w-7 text-gray-400 hover:text-gray-700 hover:bg-gray-200 border-l border-gray-200 transition-colors"
            aria-label="Cuộn phải">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
            </svg>
        </button>

        <!-- 3 chấm menu -->
        <div class="relative flex-shrink-0 border-l border-gray-200">
            <button type="button" @click.stop="toggleDotMenu"
                class="flex items-center justify-center w-8 h-full text-gray-400 hover:text-gray-700 hover:bg-gray-200 transition-colors"
                aria-label="Tùy chọn tab">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="19" r="1.5"/>
                </svg>
            </button>

            <!-- Dropdown menu -->
            <div v-if="dotMenuOpen"
                class="absolute right-0 top-full mt-0.5 w-52 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50 text-xs">
                <button type="button" @click="doCloseAll"
                    class="w-full flex items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Đóng tất cả tab
                    <span v-if="pinnedCount > 0" class="ml-auto text-amber-600 font-medium">(giữ {{ pinnedCount }} ghim)</span>
                </button>
                <div class="border-t border-gray-100 my-1"></div>
                <div class="px-3 py-1.5 text-gray-400 text-xs">
                    {{ tabs.length }} tab đang mở · {{ pinnedCount }} ghim
                </div>
            </div>
        </div>

        <!-- Right-click context menu -->
        <Teleport to="body">
            <div v-if="contextMenu.visible"
                :style="{ top: contextMenu.y + 'px', left: contextMenu.x + 'px' }"
                class="fixed z-[9999] bg-white rounded-lg shadow-xl border border-gray-200 py-1 text-xs min-w-[180px]"
                @click.stop>
                <div class="px-3 py-1.5 text-gray-400 font-medium truncate border-b border-gray-100 mb-1">
                    {{ contextMenu.tab?.title }}
                </div>
                <button type="button" @click="doPin(contextMenu.tab)"
                    class="w-full flex items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-3.5 h-3.5" :class="contextMenu.tab?.pinned ? 'text-amber-500' : 'text-gray-400'"
                        fill="currentColor" viewBox="0 0 24 24">
                        <path d="M16 1v6l2 3v2H14v5l-1 4-1-4v-5H8v-2l2-3V1h6zm-3 0H11v5.5L9 9v1h6V9l-2-2.5V1z"/>
                    </svg>
                    {{ contextMenu.tab?.pinned ? 'Bỏ ghim tab' : 'Ghim tab' }}
                </button>
                <div class="border-t border-gray-100 my-1"></div>
                <button type="button" @click="doClose(contextMenu.tab)"
                    class="w-full flex items-center gap-2.5 px-3 py-2 text-red-600 hover:bg-red-50 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Đóng tab này
                </button>
                <button type="button" @click="doCloseAll"
                    class="w-full flex items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Đóng tất cả tab
                </button>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useTabs } from '@/composables/useTabs';
import { restorePage } from '@/composables/usePageCache';
import { saveScroll, restoreScroll } from '@/composables/useScrollRestore';

const { tabs, closeTab, pinTab, closeAllTabs, reorderTabs } = useTabs();

// Dùng usePage().url (luôn reactive) thay vì tab.active để xác định tab đang xem
const page = usePage();
const currentPath = computed(() => new URL(page.url, 'http://x').pathname);
function isActive(tab) {
    return new URL(tab.url, 'http://x').pathname === currentPath.value;
}

// ── Scroll ────────────────────────────────────────────────────
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
watch(tabs, async () => {
    await nextTick();
    checkScroll();
    const active = scrollEl.value?.querySelector('[aria-selected="true"]');
    active?.scrollIntoView({ block: 'nearest', inline: 'nearest' });
}, { deep: true });
onMounted(() => nextTick().then(checkScroll));

// ── Tab click — save scroll, use cached page if available, else normal navigation ─
function clickTab(tab) {
    if (isActive(tab)) return;
    saveScroll(page.url);
    if (!restorePage(tab.url)) {
        router.visit(tab.url, { onSuccess: () => nextTick(() => restoreScroll(tab.url)) });
    } else {
        nextTick(() => restoreScroll(tab.url));
    }
}

// ── Drag & drop reorder ─────────────────────────────────────
const dragIndex     = ref(null);
const dragOverIndex = ref(null);

function onDragStart(event, index) {
    dragIndex.value = index;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', String(index));
}
function onDragOver(index) {
    if (dragIndex.value === null) return;
    dragOverIndex.value = index;
}
function onDragLeave(index) {
    if (dragOverIndex.value === index) dragOverIndex.value = null;
}
function onDrop(index) {
    if (dragIndex.value !== null) reorderTabs(dragIndex.value, index);
    dragIndex.value = null;
    dragOverIndex.value = null;
}
function onDragEnd() {
    dragIndex.value = null;
    dragOverIndex.value = null;
}

// ── 3 dots menu ───────────────────────────────────────────────
const dotMenuOpen = ref(false);
const pinnedCount = computed(() => tabs.value.filter(t => t.pinned).length);

function toggleDotMenu() { dotMenuOpen.value = !dotMenuOpen.value; }

function doCloseAll() {
    dotMenuOpen.value = false;
    contextMenu.value.visible = false;
    closeAllTabs();
}

// ── Right-click context menu ──────────────────────────────────
const contextMenu = ref({ visible: false, x: 0, y: 0, tab: null });

function openContext(event, tab) {
    dotMenuOpen.value = false;
    const vw = window.innerWidth;
    const menuW = 200;
    contextMenu.value = {
        visible: true,
        x: Math.min(event.clientX, vw - menuW - 8),
        y: event.clientY + 4,
        tab,
    };
}
function doPin(tab) {
    if (!tab) return;
    pinTab(tab.url);
    contextMenu.value.visible = false;
}
function doClose(tab) {
    if (!tab) return;
    closeTab(tab.url);
    contextMenu.value.visible = false;
}

function handleOutsideClick() {
    dotMenuOpen.value = false;
    contextMenu.value.visible = false;
}
onMounted(() => document.addEventListener('click', handleOutsideClick));
onUnmounted(() => document.removeEventListener('click', handleOutsideClick));
</script>

<style scoped>
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
