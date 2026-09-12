<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import EnrollmentController from '@/actions/App/Http/Controllers/EnrollmentController';
import Heading from '@/components/Heading.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { show } from '@/routes/courses';
import { index } from '@/routes/enrollments';
import type { Enrollment, Paginated } from '@/types';

type Props = {
    enrollments: Paginated<Enrollment>;
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'My enrollments', href: index() }],
    },
});

const statusVariant: Record<string, 'default' | 'secondary' | 'outline'> = {
    active: 'default',
    completed: 'secondary',
    cancelled: 'outline',
};
</script>

<template>
    <Head title="My enrollments" />

    <div class="space-y-6">
        <Heading
            title="My enrollments"
            description="Courses you're enrolled in"
        />

        <div
            v-if="enrollments.data.length > 0"
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
        >
            <Card v-for="enrollment in enrollments.data" :key="enrollment.id">
                <CardHeader class="flex-row items-start justify-between gap-2">
                    <CardTitle v-if="enrollment.course">
                        <Link
                            :href="show(enrollment.course)"
                            class="hover:underline"
                        >
                            {{ enrollment.course.title }}
                        </Link>
                    </CardTitle>
                    <Badge
                        :variant="statusVariant[enrollment.status]"
                        class="capitalize"
                    >
                        {{ enrollment.status }}
                    </Badge>
                </CardHeader>
                <CardContent>
                    <div
                        class="bg-muted h-2 w-full overflow-hidden rounded-full"
                    >
                        <div
                            class="bg-primary h-full"
                            :style="{
                                width: `${enrollment.progress_percent}%`,
                            }"
                        />
                    </div>
                    <p class="text-muted-foreground mt-1 text-xs">
                        {{ enrollment.progress_percent }}% complete
                    </p>
                </CardContent>
                <CardFooter
                    v-if="enrollment.status === 'active' && enrollment.course"
                >
                    <Form
                        v-bind="
                            EnrollmentController.destroy.form(enrollment.course)
                        "
                        v-slot="{ processing }"
                    >
                        <Button
                            type="submit"
                            variant="outline"
                            size="sm"
                            :disabled="processing"
                        >
                            Cancel enrollment
                        </Button>
                    </Form>
                </CardFooter>
            </Card>
        </div>
        <div
            v-else
            class="text-muted-foreground rounded-lg border border-dashed p-12 text-center text-sm"
        >
            You're not enrolled in any courses yet.
        </div>

        <Pagination :paginator="enrollments" />
    </div>
</template>
