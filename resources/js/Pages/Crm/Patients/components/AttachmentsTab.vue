<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePermission } from '@/composables/usePermission';

const { hasPermission: can } = usePermission();

const props = defineProps({
    patientId: Number,
    attachments: Array,
    attachmentTypes: Array,
});
// Cập nhật danh sách tại chỗ thay vì loadData() — loadData bật loading và nháy cả trang hồ sơ
const emit = defineEmits(['added', 'removed']);

const MAX_BYTES = 10 * 1024 * 1024;
const ACCEPT = '.jpg,.jpeg,.png,.webp,.pdf,.doc,.docx';

const filter = ref('all');
const uploadType = ref('photo');
const uploadTitle = ref('');
const queue = ref([]); // { name, status: 'pending'|'uploading'|'done'|'error', error }
const dragging = ref(false);
const fileInput = ref(null);
const viewerIndex = ref(-1);

const isImage = (a) => a.mime_type?.startsWith('image/');

const filtered = computed(() =>
    filter.value === 'all' ? props.attachments : props.attachments.filter(a => a.type === filter.value)
);
const images = computed(() => filtered.value.filter(isImage));
const documents = computed(() => filtered.value.filter(a => !isImage(a)));

// Nhóm ảnh theo ngày tải lên (created_at dạng d/m/Y H:i, danh sách đã sắp mới nhất trước)
const imageGroups = computed(() => {
    const groups = [];
    images.value.forEach((a, i) => {
        const day = a.created_at.slice(0, 10);
        let g = groups[groups.length - 1];
        if (!g || g.day !== day) groups.push(g = { day, items: [] });
        g.items.push({ ...a, index: i });
    });
    return groups;
});

const counts = computed(() => {
    const c = { all: props.attachments.length };
    props.attachments.forEach(a => { c[a.type] = (c[a.type] || 0) + 1; });
    return c;
});

const uploading = computed(() => queue.value.some(q => q.status === 'pending' || q.status === 'uploading'));

function pickFiles() {
    fileInput.value?.click();
}

function onFileChange(e) {
    enqueue([...e.target.files]);
    e.target.value = '';
}

function onDrop(e) {
    dragging.value = false;
    if (!can('patients.edit')) return;
    enqueue([...e.dataTransfer.files]);
}

async function enqueue(files) {
    if (!files.length) return;
    const items = files.map(f => ({ file: f, name: f.name, status: 'pending', error: '' }));
    queue.value.push(...items);
    // Gửi tuần tự từng file: tránh vượt post_max_size của PHP khi chọn hàng chục ảnh
    for (const item of items) {
        await uploadOne(item);
    }
    queue.value = queue.value.filter(q => q.status === 'error');
    uploadTitle.value = '';
}

async function uploadOne(item) {
    item.status = 'uploading';
    try {
        const file = await shrinkIfLarge(item.file);
        if (file.size > MAX_BYTES) throw new Error('File quá 10MB');

        const fd = new FormData();
        fd.append('type', uploadType.value);
        if (uploadTitle.value) fd.append('title', uploadTitle.value);
        fd.append('file', file, item.file.name);

        const res = await fetch(route('patient-attachments.store', props.patientId), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                'Accept': 'application/json',
            },
            body: fd,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            throw new Error(data.errors ? Object.values(data.errors).flat()[0] : (data.message || 'Tải lên thất bại'));
        }
        emit('added', data.attachment);
        item.status = 'done';
    } catch (e) {
        item.status = 'error';
        item.error = e.message;
    }
}

// Ảnh điện thoại thường 5–12MB: thu về cạnh dài 3000px, vẫn đủ nét cho ảnh lâm sàng
async function shrinkIfLarge(file) {
    if (!/^image\/(jpeg|png|webp)$/.test(file.type) || file.size <= 4 * 1024 * 1024) return file;
    try {
        const bmp = await createImageBitmap(file);
        const scale = Math.min(1, 3000 / Math.max(bmp.width, bmp.height));
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(bmp.width * scale);
        canvas.height = Math.round(bmp.height * scale);
        canvas.getContext('2d').drawImage(bmp, 0, 0, canvas.width, canvas.height);
        bmp.close();
        const blob = await new Promise(r => canvas.toBlob(r, 'image/jpeg', 0.9));
        return blob && blob.size < file.size ? new File([blob], file.name.replace(/\.\w+$/, '.jpg'), { type: 'image/jpeg' }) : file;
    } catch {
        return file;
    }
}

async function remove(a) {
    if (!confirm(`Xóa "${a.title}"?`)) return;
    const res = await fetch(route('patient-attachments.destroy', a.id), {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            'Accept': 'application/json',
        },
    });
    if (!res.ok) {
        alert('Xóa thất bại');
        return;
    }
    // Đang xem ảnh cuối thì lùi về ảnh trước, hết ảnh thì đóng viewer
    if (viewerIndex.value >= 0 && viewerIndex.value >= images.value.length - 1) {
        viewerIndex.value = images.value.length - 2;
    }
    emit('removed', a.id);
}

const current = computed(() => images.value[viewerIndex.value] ?? null);

function openViewer(i) { viewerIndex.value = i; }
function closeViewer() { viewerIndex.value = -1; }
function step(d) {
    const n = images.value.length;
    if (n) viewerIndex.value = (viewerIndex.value + d + n) % n;
}

function onKey(e) {
    if (viewerIndex.value < 0) return;
    if (e.key === 'Escape') closeViewer();
    else if (e.key === 'ArrowRight') step(1);
    else if (e.key === 'ArrowLeft') step(-1);
}
onMounted(() => document.addEventListener('keydown', onKey));
onUnmounted(() => document.removeEventListener('keydown', onKey));

function formatSize(bytes) {
    if (!bytes) return '';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

const typeChip = {
    green: 'bg-green-100 text-green-700',
    purple: 'bg-purple-100 text-purple-700',
    blue: 'bg-blue-100 text-blue-700',
    gray: 'bg-gray-100 text-gray-600',
};
</script>

<template>
    <div @dragover.prevent="dragging = can('patients.edit')" @dragleave.self="dragging = false" @drop.prevent="onDrop"
        class="relative">
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h3 class="font-semibold text-gray-700">Album & tài liệu ({{ attachments.length }})</h3>
            <div v-if="can('patients.edit')" class="flex flex-wrap items-center gap-2">
                <select v-model="uploadType" class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm">
                    <option v-for="t in attachmentTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                </select>
                <input v-model="uploadTitle" type="text" placeholder="Tiêu đề (tuỳ chọn)"
                    class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm w-44" />
                <button @click="pickFiles" :disabled="uploading"
                    class="px-3 py-1.5 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 disabled:opacity-50">
                    {{ uploading ? 'Đang tải...' : '+ Thêm ảnh / file' }}
                </button>
                <input ref="fileInput" type="file" multiple :accept="ACCEPT" class="hidden" @change="onFileChange" />
            </div>
        </div>

        <!-- Bộ lọc theo loại -->
        <div class="flex flex-wrap gap-1.5 mb-4">
            <button v-for="t in [{ value: 'all', label: 'Tất cả' }, ...attachmentTypes]" :key="t.value"
                @click="filter = t.value"
                :class="filter === t.value ? 'bg-primary-600 text-white border-primary-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'"
                class="px-3 py-1 text-xs rounded-full border">
                {{ t.label }} <span class="opacity-70">{{ counts[t.value] || 0 }}</span>
            </button>
        </div>

        <!-- Hàng đợi tải lên -->
        <div v-if="queue.length" class="mb-4 space-y-1">
            <div v-for="(q, i) in queue" :key="i" class="flex items-center gap-2 text-xs">
                <span class="truncate max-w-xs text-gray-700">{{ q.name }}</span>
                <span v-if="q.status === 'uploading'" class="text-primary-600">đang tải…</span>
                <span v-else-if="q.status === 'pending'" class="text-gray-400">chờ</span>
                <span v-else-if="q.status === 'done'" class="text-green-600">xong</span>
                <span v-else class="text-red-600">{{ q.error }}</span>
            </div>
            <button v-if="!uploading" @click="queue = []" class="text-xs text-gray-500 hover:underline">Ẩn lỗi</button>
        </div>

        <!-- Rỗng -->
        <div v-if="filtered.length === 0"
            @click="can('patients.edit') && pickFiles()"
            :class="can('patients.edit') ? 'cursor-pointer hover:bg-gray-50' : ''"
            class="text-center py-12 border-2 border-dashed border-gray-200 rounded-xl text-gray-400 text-sm">
            Chưa có ảnh/tài liệu nào<span v-if="can('patients.edit')"> — bấm hoặc kéo thả file vào đây</span>
        </div>

        <!-- Lưới ảnh theo ngày -->
        <div v-for="g in imageGroups" :key="g.day" class="mb-5">
            <p class="text-xs font-medium text-gray-500 mb-2">{{ g.day }} · {{ g.items.length }} ảnh</p>
            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
                <button v-for="a in g.items" :key="a.id" @click="openViewer(a.index)"
                    class="group relative aspect-square rounded-lg overflow-hidden bg-gray-100 border border-gray-200">
                    <img :src="a.file_url" :alt="a.title" loading="lazy"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform" />
                    <span :class="typeChip[a.type_color]"
                        class="absolute top-1 left-1 px-1.5 py-0.5 text-[10px] rounded">{{ a.type_label }}</span>
                    <span class="absolute bottom-0 inset-x-0 px-1.5 py-1 text-[11px] text-white bg-gradient-to-t from-black/60 truncate text-left opacity-0 group-hover:opacity-100">
                        {{ a.title }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Tài liệu (không phải ảnh) -->
        <div v-if="documents.length" class="space-y-2">
            <p class="text-xs font-medium text-gray-500">Tài liệu · {{ documents.length }}</p>
            <div v-for="a in documents" :key="a.id"
                class="flex items-center gap-3 p-3 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                <div class="w-10 h-10 flex-shrink-0 rounded bg-gray-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ a.title }}</p>
                    <p class="text-xs text-gray-500">{{ a.type_label }} · {{ formatSize(a.file_size) }} · {{ a.created_at }}</p>
                </div>
                <a :href="a.file_url" target="_blank" class="text-xs text-primary-600 hover:underline whitespace-nowrap">Xem</a>
                <button v-if="can('patients.edit')" @click="remove(a)" class="text-red-400 hover:text-red-600 ml-1" title="Xóa">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Lớp phủ kéo thả -->
        <div v-if="dragging"
            class="absolute inset-0 z-10 rounded-xl border-2 border-dashed border-primary-500 bg-primary-50/80 flex items-center justify-center text-primary-700 font-medium pointer-events-none">
            Thả file vào đây để tải lên
        </div>

        <!-- Xem ảnh -->
        <Teleport to="body">
            <div v-if="current" class="fixed inset-0 z-[100] bg-black/90 flex flex-col" @click.self="closeViewer">
                <div class="flex items-center gap-3 px-4 py-3 text-white text-sm">
                    <span class="font-medium truncate">{{ current.title }}</span>
                    <span class="text-white/60 whitespace-nowrap">{{ current.type_label }} · {{ current.created_at }} · {{ viewerIndex + 1 }}/{{ images.length }}</span>
                    <div class="ml-auto flex items-center gap-4">
                        <a :href="current.file_url" target="_blank" class="hover:underline">Mở gốc</a>
                        <a :href="current.file_url" :download="current.title" class="hover:underline">Tải về</a>
                        <button v-if="can('patients.edit')" @click="remove(current)" class="text-red-300 hover:text-red-200">Xóa</button>
                        <button @click="closeViewer" class="text-2xl leading-none" title="Đóng (Esc)">&times;</button>
                    </div>
                </div>
                <div class="flex-1 relative flex items-center justify-center min-h-0 px-14 pb-6" @click.self="closeViewer">
                    <img :src="current.file_url" :alt="current.title" class="max-w-full max-h-full object-contain select-none" />
                    <button v-if="images.length > 1" @click="step(-1)"
                        class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/10 hover:bg-white/25 text-white text-2xl">&lsaquo;</button>
                    <button v-if="images.length > 1" @click="step(1)"
                        class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/10 hover:bg-white/25 text-white text-2xl">&rsaquo;</button>
                </div>
            </div>
        </Teleport>
    </div>
</template>
