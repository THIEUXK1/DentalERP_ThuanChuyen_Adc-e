<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    // LoginRequest nhận trường "login": có thể là email đầy đủ hoặc chỉ tên tài khoản.
    login: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const currentYear = new Date().getFullYear();

const highlights = [
    'Hồ sơ bệnh nhân & sơ đồ răng',
    'Kế hoạch điều trị và công nợ',
    'Chấm công, lương và KPI',
];

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Đăng nhập" />

    <div class="flex min-h-screen bg-slate-50">
        <!-- Cột thương hiệu: chỉ hiện trên màn hình lớn -->
        <div
            class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-gradient-to-br from-primary-700 via-primary-600 to-primary-900 lg:flex"
        >
            <div
                class="pointer-events-none absolute -left-24 -top-24 h-96 w-96 rounded-full bg-white/10 blur-3xl"
            ></div>
            <div
                class="pointer-events-none absolute -bottom-32 -right-16 h-96 w-96 rounded-full bg-primary-300/20 blur-3xl"
            ></div>

            <div class="relative z-10 p-12">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/25 backdrop-blur"
                    >
                        <svg
                            class="h-7 w-7 text-white"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M12 5.5c-1.4-1.1-3-1.6-4.4-1.2C5.6 4.8 4.5 6.6 4.5 9c0 2.2.6 4.3 1.3 6.4.5 1.5.8 3 1 4.3.1 1 .8 1.6 1.6 1.5.8-.1 1.2-.7 1.4-1.7l.6-3.2c.2-1 .6-1.5 1.6-1.5s1.4.5 1.6 1.5l.6 3.2c.2 1 .6 1.6 1.4 1.7.8.1 1.5-.5 1.6-1.5.2-1.3.5-2.8 1-4.3.7-2.1 1.3-4.2 1.3-6.4 0-2.4-1.1-4.2-3.1-4.7-1.4-.4-3 .1-4.4 1.2z"
                            />
                        </svg>
                    </div>
                    <div class="text-xl font-semibold tracking-tight text-white">
                        Dental Clinic ERP
                    </div>
                </div>
            </div>

            <div class="relative z-10 px-12">
                <h1 class="max-w-md text-4xl font-bold leading-tight text-white">
                    Quản lý phòng khám<br />trọn vẹn trên một nền tảng
                </h1>
                <p class="mt-4 max-w-md text-base leading-relaxed text-primary-100">
                    Bệnh nhân, lịch hẹn, kế hoạch điều trị, tài chính và nhân sự —
                    tất cả trong cùng một hệ thống.
                </p>

                <ul class="mt-8 space-y-3">
                    <li
                        v-for="item in highlights"
                        :key="item"
                        class="flex items-center gap-3 text-primary-50"
                    >
                        <span
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/15 ring-1 ring-white/25"
                        >
                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    fill-rule="evenodd"
                                    d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 111.4-1.4l3.8 3.8 6.8-6.8a1 1 0 011.4 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </span>
                        <span class="text-sm">{{ item }}</span>
                    </li>
                </ul>
            </div>

            <div class="relative z-10 p-12 text-xs text-primary-200/80">
                © {{ currentYear }} Dental Clinic ERP
            </div>
        </div>

        <!-- Cột form -->
        <div class="flex w-full items-center justify-center px-5 py-10 lg:w-1/2">
            <div class="w-full max-w-md">
                <!-- Logo rút gọn cho mobile -->
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary-600"
                    >
                        <svg
                            class="h-6 w-6 text-white"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M12 5.5c-1.4-1.1-3-1.6-4.4-1.2C5.6 4.8 4.5 6.6 4.5 9c0 2.2.6 4.3 1.3 6.4.5 1.5.8 3 1 4.3.1 1 .8 1.6 1.6 1.5.8-.1 1.2-.7 1.4-1.7l.6-3.2c.2-1 .6-1.5 1.6-1.5s1.4.5 1.6 1.5l.6 3.2c.2 1 .6 1.6 1.4 1.7.8.1 1.5-.5 1.6-1.5.2-1.3.5-2.8 1-4.3.7-2.1 1.3-4.2 1.3-6.4 0-2.4-1.1-4.2-3.1-4.7-1.4-.4-3 .1-4.4 1.2z"
                            />
                        </svg>
                    </div>
                    <span class="text-lg font-semibold text-slate-800">
                        Dental Clinic ERP
                    </span>
                </div>

                <div
                    class="rounded-2xl border border-slate-200/80 bg-white p-8 shadow-xl shadow-slate-200/60 sm:p-10"
                >
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                        Đăng nhập
                    </h2>
                    <p class="mt-1.5 text-sm text-slate-500">
                        Nhập thông tin tài khoản để truy cập hệ thống.
                    </p>

                    <div
                        v-if="status"
                        class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
                    >
                        {{ status }}
                    </div>

                    <form class="mt-7 space-y-5" @submit.prevent="submit">
                        <div>
                            <label
                                for="login"
                                class="mb-1.5 block text-sm font-medium text-slate-700"
                            >
                                Tài khoản hoặc email
                            </label>
                            <div class="relative">
                                <span
                                    class="pointer-events-none absolute inset-y-0 left-0 flex w-11 items-center justify-center text-slate-400"
                                >
                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
                                        <circle cx="12" cy="7" r="4" />
                                    </svg>
                                </span>
                                <input
                                    id="login"
                                    v-model="form.login"
                                    type="text"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    placeholder="Tên tài khoản hoặc email"
                                    class="block w-full rounded-xl border-slate-300 py-2.5 pl-11 pr-3 text-slate-900 placeholder:text-slate-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30"
                                    :class="{
                                        'border-rose-400 focus:border-rose-500 focus:ring-rose-500/30':
                                            form.errors.login,
                                    }"
                                />
                            </div>
                            <p v-if="form.errors.login" class="mt-1.5 text-sm text-rose-600">
                                {{ form.errors.login }}
                            </p>
                        </div>

                        <div>
                            <div class="mb-1.5 flex items-center justify-between">
                                <label
                                    for="password"
                                    class="block text-sm font-medium text-slate-700"
                                >
                                    Mật khẩu
                                </label>
                                <Link
                                    v-if="canResetPassword"
                                    :href="route('password.request')"
                                    class="text-sm font-medium text-primary-600 hover:text-primary-700 hover:underline"
                                >
                                    Quên mật khẩu?
                                </Link>
                            </div>
                            <div class="relative">
                                <span
                                    class="pointer-events-none absolute inset-y-0 left-0 flex w-11 items-center justify-center text-slate-400"
                                >
                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <rect x="4" y="10" width="16" height="11" rx="2" />
                                        <path d="M8 10V7a4 4 0 118 0v3" />
                                    </svg>
                                </span>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    autocomplete="current-password"
                                    placeholder="Nhập mật khẩu"
                                    class="block w-full rounded-xl border-slate-300 py-2.5 pl-11 pr-11 text-slate-900 placeholder:text-slate-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30"
                                    :class="{
                                        'border-rose-400 focus:border-rose-500 focus:ring-rose-500/30':
                                            form.errors.password,
                                    }"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-xl text-slate-400 hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-primary-500/30"
                                    :aria-label="showPassword ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'"
                                    @click="showPassword = !showPassword"
                                >
                                    <svg
                                        v-if="!showPassword"
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path
                                            d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"
                                        />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                    <svg
                                        v-else
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path d="M3 3l18 18" />
                                        <path
                                            d="M10.6 6.2A9.8 9.8 0 0112 6c6.4 0 10 7 10 7a17 17 0 01-3.2 4M6.6 6.7A17 17 0 002 13s3.6 7 10 7c1.9 0 3.5-.6 4.9-1.5"
                                        />
                                        <path d="M9.9 9.9a3 3 0 004.2 4.2" />
                                    </svg>
                                </button>
                            </div>
                            <p
                                v-if="form.errors.password"
                                class="mt-1.5 text-sm text-rose-600"
                            >
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <label class="flex cursor-pointer select-none items-center gap-2.5">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                name="remember"
                                class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500/40"
                            />
                            <span class="text-sm text-slate-600">
                                Ghi nhớ đăng nhập trên thiết bị này
                            </span>
                        </label>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-primary-600/25 transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <svg
                                v-if="form.processing"
                                class="h-4 w-4 animate-spin"
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                />
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                />
                            </svg>
                            {{ form.processing ? 'Đang đăng nhập…' : 'Đăng nhập' }}
                        </button>
                    </form>
                </div>

                <p class="mt-6 text-center text-xs text-slate-400 lg:hidden">
                    © {{ currentYear }} Dental Clinic ERP
                </p>
            </div>
        </div>
    </div>
</template>
