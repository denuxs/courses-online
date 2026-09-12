<script setup lang="ts">
import { computed } from "vue";
import type { LandingStats } from "@/types";

const props = defineProps<{
    stats: LandingStats;
}>();

const formatter = new Intl.NumberFormat("es");

const items = computed(() => [
    { label: "Cursos publicados", value: props.stats.courses },
    { label: "Estudiantes inscritos", value: props.stats.students },
    { label: "Instructores", value: props.stats.instructors },
]);
</script>

<template>
    <section class="border-y bg-muted/40">
        <div
            class="mx-auto grid max-w-6xl grid-cols-1 gap-8 px-4 py-12 text-center sm:grid-cols-3 sm:px-6"
        >
            <div v-for="item in items" :key="item.label">
                <p class="text-4xl font-bold text-foreground">
                    {{ formatter.format(item.value) }}
                </p>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ item.label }}
                </p>
            </div>
        </div>
    </section>
</template>
