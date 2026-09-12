<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { BookOpen, CheckCircle, Clock } from "@lucide/vue";
import StatCard from "@/components/dashboard/StatCard.vue";
import { Button } from "@/components/ui/button";
import { index as coursesIndex } from "@/routes/courses";
import { index as enrollmentsIndex } from "@/routes/enrollments";
import type { StudentDashboardProps } from "@/types";

defineProps<Pick<StudentDashboardProps, "stats">>();
</script>

<template>
    <div class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-3">
            <StatCard
                label="Cursos activos"
                :value="stats.active_courses"
                :icon="BookOpen"
                :href="enrollmentsIndex()"
            />
            <StatCard
                label="Cursos completados"
                :value="stats.completed_courses"
                :icon="CheckCircle"
                :href="enrollmentsIndex()"
            />
            <StatCard
                label="Pagos pendientes"
                :value="stats.pending_payments"
                :icon="Clock"
            />
        </div>

        <Link :href="coursesIndex()">
            <Button>Explorar cursos</Button>
        </Link>
    </div>
</template>
