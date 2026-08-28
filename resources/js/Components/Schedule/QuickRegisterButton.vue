<template>
    <!-- Đủ điều kiện: nút mở menu chọn bệnh nhân đến sớm / đúng hẹn / muộn -->
    <div v-if="canRegister" class="inline-block" ref="root">
        <button ref="trigger" type="button" @click.prevent.stop="toggle" :disabled="busy"
            :class="['text-xs font-medium transition-colors whitespace-nowrap disabled:opacity-50', buttonClass]">
            {{ busy ? 'Đang xử lý...' : label }}
            <span class="ml-0.5">▾</span>
        </button>

        <!-- Teleport ra body: bảng lịch hẹn nằm trong khung overflow-x-auto,
             menu đặt absolute trong đó sẽ bị cắt mất. -->
        <Teleport to="body">
            <div v-if="open" ref="menu" @click.stop
                class="fixed z-50 w-48 rounded-lg border border-gray-200 bg-white py-1 shadow-lg"
                :style="{ top: pos.top + 'px', left: pos.left + 'px' }">
                <p class="px-3 py-1 text-[11px] uppercase tracking-wide text-gray-400">Bệnh nhân đến</p>
                <!-- Lịch hẹn ngày khác vẫn đăng ký được, nhưng phiếu tính cho hôm nay -->
                <p v-if="!appointment.is_today"
                    class="mx-1.5 mb-1 rounded bg-amber-50 px-2 py-1 text-[11px] leading-snug text-amber-700">
                    Lịch hẹn ngày khác — phiếu đăng ký khám tính cho hôm nay.
                </p>
                <button v-for="opt in ARRIVAL_OPTIONS" :key="opt.value" type="button"
                    @click.prevent="submit(opt.value)"
                    class="flex w-full items-center justify-between px-3 py-1.5 text-left text-xs text-gray-700 hover:bg-gray-50">
                    <span>{{ opt.label }}</span>
                    <span v-if="opt.value === appointment.arrival_suggestion"
                        class="ml-2 rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] text-emerald-600">gợi ý</span>
                </button>
            </div>
        </Teleport>
    </div>

    <!-- Không đủ điều kiện: nói rõ lý do thay vì ẩn hẳn nút, tránh nhân viên tưởng lỗi -->
    <span v-else-if="reason" class="text-xs text-gray-400 whitespace-nowrap">{{ reason }}</span>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { usePermission } from '@/composables/usePermission';

const props = defineProps({
    appointment: { type: Object, required: true },
    // 'pill' cho bảng dày (tab lịch hẹn của bệnh nhân), 'outline' cho trang Lịch hẹn
    variant: { type: String, default: 'pill' },
    label: { type: String, default: '📋 Đăng ký khám' },
});

// Trang nào tự nạp danh sách bằng fetch thì nghe sự kiện này để nạp lại phần đã đổi.
const emit = defineEmits(['registered']);

const MENU_WIDTH = 192; // w-48

const ARRIVAL_OPTIONS = [
    { value: 'arrived_early', label: 'Đến trước hẹn' },
    { value: 'checked_in', label: 'Đến đúng hẹn' },
    { value: 'arrived_late', label: 'Đến sau hẹn' },
];

// Chỉ lịch hẹn chưa chốt trạng thái đến mới đăng ký khám được. Ngày hẹn không giới hạn:
// bệnh nhân đến sớm/trễ vài ngày vẫn phải chốt được, phiếu đăng ký luôn tính cho hôm nay.
const REGISTERABLE_STATUSES = ['booked', 'confirmed'];

const { hasPermission } = usePermission();

const busy = ref(false);
const open = ref(false);
const root = ref(null);
const trigger = ref(null);
const menu = ref(null);
const pos = ref({ top: 0, left: 0 });

const buttonClass = computed(() => props.variant === 'outline'
    ? 'px-2.5 py-1.5 rounded-lg text-teal-700 border border-teal-200 bg-teal-50 hover:bg-teal-100'
    : 'px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 hover:bg-emerald-100');

const canRegister = computed(() => {
    const a = props.appointment;
    return hasPermission('appointments.manage')
        && REGISTERABLE_STATUSES.includes(a.status)
        && !a.has_registration;
});

const reason = computed(() => {
    const a = props.appointment;
    if (!hasPermission('appointments.manage')) return null;
    if (a.has_registration) return 'Đã đăng ký khám';
    // Lịch hẹn đã chốt (đến / huỷ / không đến) thì nút không còn ý nghĩa.
    return null;
});

function place() {
    if (!trigger.value) return;
    const r = trigger.value.getBoundingClientRect();
    const height = menu.value?.offsetHeight ?? 120;
    // Canh phải theo nút, lật lên trên nếu chạm đáy màn hình.
    const left = Math.max(8, Math.min(r.right - MENU_WIDTH, window.innerWidth - MENU_WIDTH - 8));
    const top = r.bottom + height + 8 > window.innerHeight ? r.top - height - 4 : r.bottom + 4;
    pos.value = { top, left };
}

async function toggle() {
    open.value = !open.value;
    if (!open.value) return;
    place();
    await nextTick();
    place(); // đo lại khi đã biết chiều cao thật của menu
}

function submit(arrivalStatus) {
    if (busy.value) return;
    busy.value = true;
    open.value = false;
    router.post(route('schedule.appointments.quick-register', props.appointment.id),
        { arrival_status: arrivalStatus },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => emit('registered'),
            onFinish: () => { busy.value = false; },
        });
}

function onOutside(e) {
    if (open.value && root.value && !root.value.contains(e.target)) open.value = false;
}
function onViewportChange() {
    if (open.value) open.value = false;
}

onMounted(() => {
    document.addEventListener('click', onOutside);
    // Menu đặt fixed nên phải đóng khi trang cuộn, tránh trôi khỏi nút.
    window.addEventListener('scroll', onViewportChange, true);
    window.addEventListener('resize', onViewportChange);
});
onUnmounted(() => {
    document.removeEventListener('click', onOutside);
    window.removeEventListener('scroll', onViewportChange, true);
    window.removeEventListener('resize', onViewportChange);
});
</script>
