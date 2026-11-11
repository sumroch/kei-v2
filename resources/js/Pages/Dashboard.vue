<script setup>
import { computed } from 'vue';
import { Deferred, Head, Link, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ArrowRight, CalendarCheck, Car, Images, Inbox, LayoutDashboard, TrendingUp } from 'lucide-vue-next';
import AdminLayout from '../Layouts/AdminLayout.vue';
import PageHeader from '../Components/PageHeader.vue';
import TypeBadge from '../Components/TypeBadge.vue';
import { useT } from '../i18n';

defineOptions({ layout: AdminLayout });

// Both deferred (Inertia::defer): undefined until the follow-up request returns.
const props = defineProps({
    stats: { type: Object, default: undefined },
    latest: { type: Array, default: undefined },
});

const t = useT();
const user = computed(() => usePage().props.auth.user);
const byType = computed(() => ['contact', 'raiten', 'shinsa'].map((type) => ({ type, total: props.stats?.reservations_month_by_type?.[type] ?? 0 })));

const cards = computed(() => [
    { key: 'dashboard.thisMonth', value: props.stats?.reservations_month, icon: CalendarCheck, tone: 'bg-primary/10 text-primary', href: route('reservations.index') },
    { key: 'dashboard.total', value: props.stats?.reservations_total, icon: TrendingUp, tone: 'bg-info/10 text-info', href: route('reservations.index') },
    { key: 'dashboard.activeCars', value: props.stats?.cars_active, icon: Car, tone: 'bg-success/10 text-success', href: route('cars.index') },
    { key: 'dashboard.photos', value: props.stats?.photos, icon: Images, tone: 'bg-warning/15 text-warning', href: route('photos.index') },
]);
</script>

<template>
    <Head :title="t('nav.dashboard')" />

    <PageHeader :title="t('dashboard.welcome', { name: user.name })" :description="t('desc.dashboard')" :icon="LayoutDashboard" />

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <Link
            v-for="(card, i) in cards"
            :key="card.key"
            :href="card.href"
            class="group rounded-box border border-base-300 bg-base-100 p-5 shadow-sm transition hover:border-primary/40 hover:shadow-md"
        >
            <div class="flex items-start justify-between gap-3">
                <p class="text-sm font-medium text-base-content/60">{{ t(card.key) }}</p>
                <span class="grid size-10 shrink-0 place-items-center rounded-box" :class="card.tone">
                    <component :is="card.icon" class="size-5" aria-hidden="true" />
                </span>
            </div>
            <Deferred data="stats">
                <template #fallback>
                    <div class="skeleton mt-2 h-9 w-24"></div>
                    <div class="skeleton mt-3 h-4 w-40"></div>
                </template>
                <p class="mt-1 text-3xl font-bold tracking-tight tabular-nums">{{ (card.value ?? 0).toLocaleString() }}</p>
                <!-- breakdown of this month by type, on the first card -->
                <div v-if="i === 0" class="mt-3 flex flex-wrap gap-1.5">
                    <span v-for="row in byType" :key="row.type" class="badge badge-sm badge-ghost gap-1">
                        {{ t(`type.${row.type}`) }} <strong class="tabular-nums">{{ row.total }}</strong>
                    </span>
                </div>
            </Deferred>
        </Link>
    </div>

    <section class="mt-6 overflow-hidden rounded-box border border-base-300 bg-base-100 shadow-sm">
        <div class="flex items-center justify-between gap-4 border-b border-base-300 px-5 py-4">
            <h2 class="font-semibold">{{ t('dashboard.latest') }}</h2>
            <Link :href="route('reservations.index')" class="btn btn-ghost btn-sm text-primary">
                {{ t('dashboard.seeAll') }}
                <ArrowRight class="size-4" aria-hidden="true" />
            </Link>
        </div>

        <Deferred data="latest">
            <template #fallback>
                <ul class="divide-y divide-base-200" aria-busy="true">
                    <li v-for="n in 5" :key="n" class="flex items-center gap-3 px-5 py-3.5">
                        <div class="skeleton size-9 rounded-full"></div>
                        <div class="flex-1 space-y-2">
                            <div class="skeleton h-4 w-40"></div>
                            <div class="skeleton h-3 w-28"></div>
                        </div>
                        <div class="skeleton h-4 w-28"></div>
                    </li>
                </ul>
            </template>

            <div v-if="!latest.length" class="flex flex-col items-center gap-2 py-12 text-base-content/60">
                <Inbox class="size-6" aria-hidden="true" />
                {{ t('common.empty') }}
            </div>
            <ul v-else class="divide-y divide-base-200">
                <li v-for="r in latest" :key="r.id" class="flex flex-wrap items-center gap-x-3 gap-y-1 px-5 py-3 transition-colors hover:bg-base-200/40">
                    <span class="grid size-9 shrink-0 place-items-center rounded-full bg-base-200 text-sm font-semibold text-base-content/70" aria-hidden="true">
                        {{ (r.name ?? '?').trim().slice(0, 1) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium">{{ r.name }}</p>
                        <p class="truncate text-sm text-base-content/50">{{ r.store }}</p>
                    </div>
                    <TypeBadge :type="r.type" />
                    <span class="w-full text-right text-sm text-base-content/50 tabular-nums sm:w-36">{{ r.created_at }}</span>
                </li>
            </ul>
        </Deferred>
    </section>
</template>
