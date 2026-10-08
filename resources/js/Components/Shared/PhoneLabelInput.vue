<template>
    <!-- Nhãn chủ số điện thoại: gõ tự do, datalist chỉ gợi ý các quan hệ hay gặp. -->
    <input :value="modelValue ?? ''" @input="$emit('update:modelValue', $event.target.value)"
        :list="listId" maxlength="30" placeholder="Của ai? (Bố, Mẹ…)"
        class="w-28 rounded-lg border border-gray-300 px-2 py-1.5 text-xs focus:ring-2 focus:ring-indigo-300 focus:outline-none" />
    <datalist :id="listId">
        <option v-for="o in PHONE_LABEL_OPTIONS" :key="o" :value="o" />
    </datalist>
</template>

<script setup>
defineProps({ modelValue: String });
defineEmits(['update:modelValue']);

const PHONE_LABEL_OPTIONS = ['Bản thân', 'Bố', 'Mẹ', 'Vợ', 'Chồng', 'Con', 'Ông', 'Bà', 'Anh', 'Chị', 'Em', 'Người thân'];

// Mỗi instance một id riêng để nhiều ô trên cùng form không đụng nhau.
const listId = `phone-label-${Math.random().toString(36).slice(2, 9)}`;
</script>
