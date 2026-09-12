<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { show } from '@/routes/courses';
import type { Course } from '@/types';

type Props = {
    course: Course;
    showStatus?: boolean;
};

withDefaults(defineProps<Props>(), {
    showStatus: false,
});

const statusVariant: Record<string, 'default' | 'secondary' | 'outline'> = {
    draft: 'outline',
    published: 'default',
    archived: 'secondary',
};
</script>

<template>
    <Card class="flex h-full flex-col">
        <CardHeader>
            <div class="flex items-start justify-between gap-2">
                <Badge v-if="course.category" variant="secondary">
                    {{ course.category.name }}
                </Badge>
                <Badge
                    v-if="showStatus"
                    :variant="statusVariant[course.status]"
                    class="capitalize"
                >
                    {{ course.status }}
                </Badge>
            </div>
            <CardTitle>
                <Link :href="show(course)" class="hover:underline">
                    {{ course.title }}
                </Link>
            </CardTitle>
            <CardDescription v-if="course.instructor">
                By {{ course.instructor.name }}
            </CardDescription>
        </CardHeader>
        <CardContent class="flex-1">
            <p
                v-if="course.description"
                class="text-muted-foreground line-clamp-3 text-sm"
            >
                {{ course.description }}
            </p>
        </CardContent>
        <CardFooter class="flex items-center justify-between text-sm">
            <span class="text-muted-foreground">
                {{ course.lessons_count ?? 0 }} lessons
            </span>
            <span class="font-medium">
                {{ Number(course.price) > 0 ? `$${course.price}` : 'Free' }}
            </span>
        </CardFooter>
    </Card>
</template>
