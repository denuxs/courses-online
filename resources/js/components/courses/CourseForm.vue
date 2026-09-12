<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import type { Category, Course } from '@/types';

type Props = {
    course?: Course;
    categories: Category[];
    errors: Partial<Record<string, string>>;
};

defineProps<Props>();

const textareaClass = cn(
    'border-input placeholder:text-muted-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-24 w-full rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
);

const selectClass = cn(
    'border-input focus-visible:border-ring focus-visible:ring-ring/50 dark:bg-input/30 h-9 w-full rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] md:text-sm',
);
</script>

<template>
    <div class="grid gap-6">
        <div class="grid gap-2">
            <Label for="title">Title</Label>
            <Input
                id="title"
                name="title"
                :default-value="course?.title"
                required
                placeholder="Course title"
            />
            <InputError :message="errors.title" />
        </div>

        <div class="grid gap-2">
            <Label for="slug">Slug</Label>
            <Input
                id="slug"
                name="slug"
                :default-value="course?.slug"
                required
                placeholder="course-title"
            />
            <InputError :message="errors.slug" />
        </div>

        <div class="grid gap-2">
            <Label for="category_id">Category</Label>
            <select
                id="category_id"
                name="category_id"
                :class="selectClass"
                :default-value="course?.category_id ?? ''"
            >
                <option value="">No category</option>
                <option
                    v-for="category in categories"
                    :key="category.id"
                    :value="category.id"
                    :selected="category.id === course?.category_id"
                >
                    {{ category.name }}
                </option>
            </select>
            <InputError :message="errors.category_id" />
        </div>

        <div class="grid gap-2">
            <Label for="description">Description</Label>
            <textarea
                id="description"
                name="description"
                :class="textareaClass"
                placeholder="What will students learn in this course?"
                >{{ course?.description }}</textarea>
            <InputError :message="errors.description" />
        </div>

        <div class="grid gap-2">
            <Label for="price">Price</Label>
            <Input
                id="price"
                name="price"
                type="number"
                step="0.01"
                min="0"
                :default-value="course?.price ?? '0'"
                required
                placeholder="0.00"
            />
            <InputError :message="errors.price" />
        </div>

        <div v-if="course" class="grid gap-2">
            <Label for="status">Status</Label>
            <select
                id="status"
                name="status"
                :class="selectClass"
                :default-value="course.status"
            >
                <option value="draft" :selected="course.status === 'draft'">
                    Draft
                </option>
                <option
                    value="published"
                    :selected="course.status === 'published'"
                >
                    Published
                </option>
                <option
                    value="archived"
                    :selected="course.status === 'archived'"
                >
                    Archived
                </option>
            </select>
            <InputError :message="errors.status" />
        </div>
    </div>
</template>
