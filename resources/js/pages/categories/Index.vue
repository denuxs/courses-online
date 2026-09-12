<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Card, CardHeader, CardTitle } from '@/components/ui/card';
import { index, show } from '@/routes/categories';
import type { Category } from '@/types';

type Props = {
    categories: Category[];
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Categories', href: index() }],
    },
});
</script>

<template>
    <Head title="Categories" />

    <div class="space-y-6">
        <Heading title="Categories" description="Browse courses by category" />

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="category in categories"
                :key="category.id"
                :href="show(category)"
            >
                <Card class="hover:bg-accent transition-colors">
                    <CardHeader class="flex-row items-center justify-between">
                        <CardTitle>{{ category.name }}</CardTitle>
                        <span class="text-muted-foreground text-sm">
                            {{ category.courses_count ?? 0 }} courses
                        </span>
                    </CardHeader>
                </Card>
            </Link>
        </div>
    </div>
</template>
