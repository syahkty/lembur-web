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
        ? 'inline-flex items-center rounded-lg px-3 py-2 text-sm font-semibold bg-emerald-50 text-emerald-800 transition'
        : 'inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
