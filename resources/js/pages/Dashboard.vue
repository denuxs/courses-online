<script setup lang="ts">
import { Head, usePage } from "@inertiajs/vue3";
import AdminDashboard from "@/components/dashboard/AdminDashboard.vue";
import InstructorDashboard from "@/components/dashboard/InstructorDashboard.vue";
import StudentDashboard from "@/components/dashboard/StudentDashboard.vue";
import Heading from "@/components/Heading.vue";
import { dashboard } from "@/routes";
import type { DashboardProps } from "@/types";

const props = defineProps<DashboardProps>();

const page = usePage();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: "Dashboard",
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="space-y-6">
        <Heading
            title="Dashboard"
            :description="`Hola, ${page.props.auth.user.name}`"
        />

        <StudentDashboard
            v-if="props.role === 'student'"
            :stats="props.stats"
        />
        <InstructorDashboard
            v-else-if="props.role === 'instructor'"
            :stats="props.stats"
            :courses="props.courses"
            :recent_enrollments="props.recent_enrollments"
        />
        <AdminDashboard
            v-else
            :stats="props.stats"
            :pending_payments="props.pending_payments"
        />
    </div>
</template>
