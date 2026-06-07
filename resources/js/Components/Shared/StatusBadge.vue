<template>
    <span
        :class="[
            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
            colorClasses[color] ?? colorClasses.gray,
        ]"
    >
        <slot />
    </span>
</template>

<script setup>
defineProps({ color: { type: String, default: 'gray' } });

const colorClasses = {
    gray:   'bg-gray-100 text-gray-700',
    green:  'bg-green-100 text-green-700',
    red:    'bg-red-100 text-red-700',
    yellow: 'bg-yellow-100 text-yellow-700',
    blue:   'bg-blue-100 text-blue-700',
    purple: 'bg-purple-100 text-purple-700',
    teal:   'bg-teal-100 text-teal-700',
    orange: 'bg-orange-100 text-orange-700',
    pink:   'bg-pink-100 text-pink-700',
};
</script>
