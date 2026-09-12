<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CourseCard from '@/components/courses/CourseCard.vue';
import Heading from '@/components/Heading.vue';
import Pagination from '@/components/Pagination.vue';
import { index, show } from '@/routes/categories';
import type { Category, Course, Paginated } from '@/types';

type Props = {
    category: Category;
    courses: Paginated<Course>;
};

defineProps<Props>();

defineOptions({
    layout: (pageProps: Props) => ({
        breadcrumbs: [
            { title: 'Categories', href: index() },
            { title: pageProps.category.name, href: show(pageProps.category) },
        ],
    }),
});
</script>

<template>
    <Head :title="category.name" />

    <div class="space-y-6">
        <Heading :title="category.name" />

        <div
            v-if="courses.data.length > 0"
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
        >
            <CourseCard
                v-for="course in courses.data"
                :key="course.id"
                :course="course"
            />
        </div>
        <div
            v-else
            class="text-muted-foreground rounded-lg border border-dashed p-12 text-center text-sm"
        >
            No courses in this category yet.
        </div>

        <Pagination :paginator="courses" />
    </div>
</template>
