<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { CircleAlert, Eye, EyeOff, Lock, LogIn, Mail } from 'lucide-vue-next';
import { useT } from '../../i18n';
import logo from '../../../images/logo.png';

const t = useT();
const form = useForm({ email: '', password: '', remember: false });
const showPassword = ref(false);

const submit = () => {
    form.post(route('login.store'), { onFinish: () => form.reset('password') });
};
</script>

<template>
    <Head :title="t('auth.login')" />
    <div class="flex min-h-screen flex-col bg-base-200">
        <header class="border-b border-base-300 bg-base-100 px-4 py-3 sm:px-6">
            <img :src="logo" alt="KEI" class="h-8 w-auto" />
        </header>

        <main class="flex flex-1 items-start justify-center px-4 py-12 sm:items-center sm:py-16">
            <form class="w-full max-w-sm overflow-hidden rounded-box border border-base-300 bg-base-100 shadow-lg" novalidate @submit.prevent="submit">
                <div class="border-b border-base-300 bg-accent/60 px-8 py-6 text-center">
                    <img :src="logo" alt="" class="mx-auto h-9 w-auto" />
                </div>

                <div class="space-y-5 px-8 py-7">
                    <div>
                        <h1 class="text-xl font-bold tracking-tight">{{ t('auth.title') }}</h1>
                        <p class="mt-1 text-sm text-base-content/60">{{ t('auth.subtitle') }}</p>
                    </div>

                    <div v-if="form.errors.email" role="alert" class="alert alert-error alert-soft text-sm">
                        <CircleAlert class="size-4" aria-hidden="true" />
                        <span>{{ form.errors.email }}</span>
                    </div>

                    <div class="space-y-1.5">
                        <label for="login-email" class="text-sm font-medium">{{ t('auth.email') }}</label>
                        <label class="input w-full" :class="{ 'input-error': form.errors.email }">
                            <Mail class="size-4 text-base-content/40" aria-hidden="true" />
                            <input id="login-email" v-model="form.email" type="text" class="grow" autocomplete="email" required autofocus />
                        </label>
                    </div>

                    <div class="space-y-1.5">
                        <label for="login-password" class="text-sm font-medium">{{ t('auth.password') }}</label>
                        <label class="input w-full">
                            <Lock class="size-4 text-base-content/40" aria-hidden="true" />
                            <input id="login-password" v-model="form.password" :type="showPassword ? 'text' : 'password'" class="grow" autocomplete="current-password" required />
                            <button
                                type="button"
                                class="btn btn-ghost btn-xs btn-square -mr-1"
                                :aria-label="showPassword ? t('auth.hidePassword') : t('auth.showPassword')"
                                :aria-pressed="showPassword"
                                @click="showPassword = !showPassword"
                            >
                                <EyeOff v-if="showPassword" class="size-4" aria-hidden="true" />
                                <Eye v-else class="size-4" aria-hidden="true" />
                            </button>
                        </label>
                    </div>

                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                        <input v-model="form.remember" type="checkbox" class="checkbox checkbox-sm checkbox-primary" />
                        {{ t('auth.remember') }}
                    </label>

                    <button type="submit" class="btn btn-primary w-full" :disabled="form.processing">
                        <span v-if="form.processing" class="loading loading-spinner loading-sm"></span>
                        <LogIn v-else class="size-4" aria-hidden="true" />
                        {{ t('auth.login') }}
                    </button>
                </div>
            </form>
        </main>

        <footer class="py-6 text-center text-xs text-base-content/40">© Akiyama Group</footer>
    </div>
</template>
