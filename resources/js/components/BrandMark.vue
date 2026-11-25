<script setup>
defineProps({
    size: {
        type: [Number, String],
        default: 20,
    },
});
</script>

<template>
    <svg
        :width="size"
        :height="size"
        viewBox="0 0 24 24"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
    >
        <rect
            x="4"
            y="2.5"
            width="16"
            height="19"
            rx="4"
            stroke="currentColor"
            stroke-width="2"
        />
        <path
            d="M9.5 6.5H14.5"
            stroke="var(--accent)"
            stroke-width="2"
            stroke-linecap="round"
        />
        <circle cx="12" cy="17.5" r="1.25" fill="var(--accent)" />
    </svg>
</template>
