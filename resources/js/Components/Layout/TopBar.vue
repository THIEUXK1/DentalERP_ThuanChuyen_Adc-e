<template>
    <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6 shadow-sm">
        <div class="flex items-center gap-3">
            <h1 class="text-base font-semibold text-gray-800">{{ title }}</h1>
        </div>
        <div class="flex items-center gap-4">
            <!-- Notifications -->
            <Link href="/notifications" class="relative text-gray-500 hover:text-gray-700 p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <span v-if="unreadCount > 0"
                    class="absolute -top-0.5 -right-0.5 w-4 h-4 rounded-full bg-red-500 text-white text-[9px] font-bold flex items-center justify-center">
                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                </span>
            </Link>

            <!-- User dropdown -->
            <div class="relative" ref="dropdownRef">
                <button
                    @click="open = !open"
                    class="flex items-center gap-2 text-sm text-gray-700 hover:text-gray-900"
                >
                    <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white text-xs font-bold">
                        {{ initials }}
                    </div>
                    <span class="hidden sm:block">{{ user?.name }}</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div
                    v-if="open"
                    class="absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-lg z-50 py-1"
                >
                    <Link href="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        Hồ sơ
                    </Link>
                    <Link href="/logout" method="post" as="button"
                        class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                        Đăng xuất
                    </Link>
                </div>
            </div>
        </div>
    </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

defineProps({ title: { type: String, default: '' } });

const page        = usePage();
const user        = computed(() => page.props.auth?.user);
const unreadCount = computed(() => page.props.notifications?.unread ?? 0);
const open        = ref(false);
const dropdownRef = ref(null);

const initials = computed(() => {
    if (!user.value?.name) return '?';
    return user.value.name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
});

function handleClickOutside(e) {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) open.value = false;
}
onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
</script>
