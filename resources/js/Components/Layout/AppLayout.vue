<template>
    <Head :title="props.title" />

    <div :class="['bg-gray-50', fullHeight ? 'h-screen overflow-hidden' : 'min-h-screen']">
        <Sidebar :collapsed="collapsed" @toggle="collapsed = !collapsed" />

        <!-- Main area -->
        <div :class="['flex flex-col transition-all duration-200', collapsed ? 'ml-16' : 'ml-60',
                      fullHeight ? 'h-screen' : 'min-h-screen']">
            <TopBar :title="title" />
            <TabBar />

            <!-- fullHeight: the page owns the viewport and only inner panes scroll -->
            <main :class="['flex-1 p-6 flex flex-col', fullHeight ? 'min-h-0 overflow-hidden' : '']">
                <slot />
            </main>
        </div>

        <FlashMessage />
    </div>
</template>

<script setup>
import { ref, toRefs } from 'vue';
import { Head } from '@inertiajs/vue3';
import Sidebar from './Sidebar.vue';
import TopBar from './TopBar.vue';
import TabBar from './TabBar.vue';
import FlashMessage from './FlashMessage.vue';

const props = defineProps({
    title: { type: String, default: '' },
    // Khóa chiều cao theo viewport: trang không cuộn, chỉ vùng nội dung bên trong cuộn.
    fullHeight: { type: Boolean, default: false },
});

const { fullHeight } = toRefs(props);

const collapsed = ref(false);
</script>

<style>
@media print {
    /* Hide sidebar, topbar, tabbar */
    aside,
    header,
    #tabbar-root { display: none !important; }

    /* Remove left margin added by sidebar */
    .ml-60,
    .ml-16 { margin-left: 0 !important; }

    /* Remove padding, reset background */
    main { padding: 0 !important; }
    body, .min-h-screen { background: #fff !important; }
}
</style>
