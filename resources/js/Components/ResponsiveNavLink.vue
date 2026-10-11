<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'block w-full rounded-lg px-3 py-2 text-start text-base font-semibold bg-emerald-50 text-emerald-800 transition'
        : 'block w-full rounded-lg px-3 py-2 text-start text-base font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-800 transition',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
