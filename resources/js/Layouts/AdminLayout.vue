<script setup>
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { CalendarCheck, Car, ChevronDown, GalleryHorizontalEnd, Images, LayoutDashboard, LogOut, Menu, PanelLeftClose, PanelLeftOpen, Store } from 'lucide-vue-next';
import ConfirmDialog from '../Components/ConfirmDialog.vue';
import Toast from '../Components/Toast.vue';
import { useT } from '../i18n';
import logo from '../../images/logo.png';
import flagEn from '../../images/flags/en.svg';
import flagJa from '../../images/flags/ja.svg';

const t = useT();
const page = usePage();
const user = computed(() => page.props.auth.user);
const initials = computed(() => (user.value?.name ?? '?').trim().slice(0, 1).toUpperCase());

// `match` is a Ziggy route-name pattern used for the active state.
const nav = [
    { name: 'dashboard', match: 'dashboard', key: 'nav.dashboard', icon: LayoutDashboard },
    { name: 'reservations.index', match: 'reservations.*', key: 'nav.reservations', icon: CalendarCheck },
    { name: 'photos.index', match: 'photos.*', key: 'nav.photos', icon: Images },
    { name: 'cars.index', match: 'cars.*', key: 'nav.cars', icon: Car },
    { name: 'slides.index', match: 'slides.*', key: 'nav.slides', icon: GalleryHorizontalEnd },
    { name: 'stores.index', match: 'stores.*', key: 'nav.stores', icon: Store },
];
// Re-evaluated on every visit because page.url changes.
const isActive = (pattern) => (page.url, route().current(pattern));

const languages = [
    { code: 'ja', label: '日本語', flag: flagJa },
    { code: 'en', label: 'English', flag: flagEn },
];
const currentLanguage = computed(() => languages.find((l) => l.code === page.props.locale) ?? languages[0]);
const setLang = (lang) => router.post(route('language'), { lang }, { preserveScroll: true, preserveState: true });

// Desktop: collapsible sidebar (remembered per browser). Mobile: off-canvas drawer.
const COLLAPSE_KEY = 'kei.sidebar.collapsed';
const readCollapsed = () => {
    try {
        return localStorage.getItem(COLLAPSE_KEY) === '1';
    } catch {
        return false;
    }
};
const collapsed = ref(readCollapsed());
const toggleCollapsed = () => {
    collapsed.value = !collapsed.value;
    try {
        localStorage.setItem(COLLAPSE_KEY, collapsed.value ? '1' : '0');
    } catch {
        // storage unavailable (private mode): keep it in memory only
    }
};

const drawerOpen = ref(false);
router.on('navigate', () => (drawerOpen.value = false));
</script>

<template>
    <div class="drawer lg:drawer-open min-h-screen bg-base-200">
        <input id="admin-drawer" v-model="drawerOpen" type="checkbox" class="drawer-toggle" />

        <div class="drawer-content flex min-w-0 flex-col">
            <header class="sticky top-0 z-20 flex h-16 items-center gap-2 border-b border-base-300 bg-base-100/90 px-3 backdrop-blur sm:px-6">
                <label for="admin-drawer" class="btn btn-ghost btn-square btn-sm lg:hidden" :aria-label="t('nav.menu')">
                    <Menu class="size-5" aria-hidden="true" />
                </label>
                <button
                    type="button"
                    class="btn btn-ghost btn-square btn-sm hidden lg:inline-flex"
                    :aria-label="t('nav.toggleSidebar')"
                    :aria-expanded="!collapsed"
                    @click="toggleCollapsed"
                >
                    <component :is="collapsed ? PanelLeftOpen : PanelLeftClose" class="size-5 text-base-content/70" aria-hidden="true" />
                </button>
                <img :src="logo" alt="KEI" class="h-7 w-auto lg:hidden" />

                <div class="flex-1"></div>

                <div class="dropdown dropdown-end">
                    <div tabindex="0" role="button" class="btn btn-ghost btn-sm gap-2 font-normal" :aria-label="t('nav.language')">
                        <img :src="currentLanguage.flag" alt="" class="size-4 rounded-full" />
                        <span class="hidden sm:inline">{{ currentLanguage.label }}</span>
                        <ChevronDown class="size-3.5 text-base-content/50" aria-hidden="true" />
                    </div>
                    <ul tabindex="0" class="dropdown-content menu z-30 mt-2 w-40 rounded-box border border-base-300 bg-base-100 p-1.5 shadow-lg">
                        <li v-for="lang in languages" :key="lang.code">
                            <button type="button" :class="{ 'menu-active': lang.code === currentLanguage.code }" @click="setLang(lang.code)">
                                <img :src="lang.flag" alt="" class="size-4 rounded-full" />
                                {{ lang.label }}
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="dropdown dropdown-end">
                    <div tabindex="0" role="button" class="btn btn-ghost btn-sm h-10 gap-2 px-1.5 font-normal sm:px-2">
                        <span class="grid size-8 place-items-center rounded-full bg-primary text-sm font-semibold text-primary-content" aria-hidden="true">{{ initials }}</span>
                        <span class="hidden max-w-40 flex-col items-start leading-tight sm:flex">
                            <span class="truncate text-sm font-medium">{{ user.name }}</span>
                            <span class="truncate text-xs text-base-content/50">{{ user.store ?? t('nav.headOffice') }}</span>
                        </span>
                        <ChevronDown class="hidden size-3.5 text-base-content/50 sm:block" aria-hidden="true" />
                    </div>
                    <ul tabindex="0" class="dropdown-content menu z-30 mt-2 w-56 rounded-box border border-base-300 bg-base-100 p-1.5 shadow-lg">
                        <li class="menu-title">
                            <span class="truncate text-base-content">{{ user.name }}</span>
                            <span class="truncate text-xs font-normal">{{ user.email }}</span>
                        </li>
                        <li>
                            <Link :href="route('logout')" method="post" as="button" class="text-error">
                                <LogOut class="size-4" aria-hidden="true" />
                                {{ t('nav.logout') }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </header>

            <main class="mx-auto w-full max-w-[1600px] flex-1 p-4 sm:p-6 lg:p-8">
                <slot />
            </main>
        </div>

        <div class="drawer-side z-30">
            <label for="admin-drawer" class="drawer-overlay" :aria-label="t('common.close')"></label>
            <aside class="flex min-h-full flex-col bg-secondary text-secondary-content transition-[width] duration-200" :class="collapsed ? 'w-64 lg:w-[4.5rem]' : 'w-64'">
                <Link :href="route('dashboard')" class="flex h-16 shrink-0 items-center justify-center border-b border-black/10 bg-base-100 px-4">
                    <!-- Collapsed desktop sidebar is too narrow for the wide logo: show a monogram there. -->
                    <img :src="logo" alt="KEI" class="h-8 w-auto" :class="{ 'lg:hidden': collapsed }" />
                    <span v-if="collapsed" class="hidden size-9 place-items-center rounded-box bg-secondary text-lg font-bold text-secondary-content lg:grid" aria-hidden="true">K</span>
                </Link>

                <nav class="flex-1 overflow-y-auto px-3 py-4" :aria-label="t('nav.menu')">
                    <p class="mb-2 px-3 text-[11px] font-semibold tracking-wider text-secondary-content/50 uppercase" :class="{ 'lg:sr-only': collapsed }">{{ t('nav.menu') }}</p>
                    <ul class="space-y-1">
                        <li v-for="item in nav" :key="item.name">
                            <Link
                                :href="route(item.name)"
                                class="group flex items-center gap-3 rounded-field px-3 py-2.5 text-sm font-medium transition-colors"
                                :class="[
                                    isActive(item.match) ? 'bg-base-100 text-secondary shadow-sm' : 'text-secondary-content/80 hover:bg-white/10 hover:text-secondary-content',
                                    collapsed ? 'lg:tooltip lg:tooltip-right lg:justify-center lg:px-0' : '',
                                ]"
                                :data-tip="t(item.key)"
                                :aria-current="isActive(item.match) ? 'page' : undefined"
                            >
                                <component :is="item.icon" class="size-5 shrink-0" aria-hidden="true" />
                                <span :class="{ 'lg:sr-only': collapsed }">{{ t(item.key) }}</span>
                            </Link>
                        </li>
                    </ul>
                </nav>

                <p class="px-6 py-4 text-[11px] text-secondary-content/40" :class="{ 'lg:hidden': collapsed }">© Akiyama Group</p>
            </aside>
        </div>

        <Toast />
        <ConfirmDialog />
    </div>
</template>
