<template>
    <div class="min-h-screen bg-gray-50">
        <Sidebar :collapsed="collapsed" @toggle="collapsed = !collapsed" />

        <!-- Main area -->
        <div :class="['flex flex-col min-h-screen transition-all duration-200', collapsed ? 'ml-16' : 'ml-60']">
            <TopBar :title="title" />
            <TabBar />

            <main class="flex-1 p-6">
                <slot />
            </main>
        </div>

        <FlashMessage />
    </div>
</template>

<script setup>
import { ref } from 'vue';
import Sidebar from './Sidebar.vue';
import TopBar from './TopBar.vue';
import TabBar from './TabBar.vue';
import FlashMessage from './FlashMessage.vue';

defineProps({
    title: { type: String, default: '' },
});

const collapsed = ref(false);
</script>
