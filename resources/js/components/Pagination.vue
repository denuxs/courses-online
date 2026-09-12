<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';

type Props = {
    paginator: Paginated<unknown>;
};

defineProps<Props>();
</script>

<template>
    <nav
        v-if="paginator.last_page > 1"
        class="flex flex-wrap items-center justify-center gap-1"
        aria-label="Pagination"
    >
        <Button
            v-for="(link, index) in paginator.links"
            :key="index"
            variant="outline"
            size="sm"
            :disabled="link.url === null"
            :class="{ 'bg-muted': link.active }"
            :as-child="link.url !== null"
        >
            <Link
                v-if="link.url"
                :href="link.url"
                preserve-scroll
                v-html="link.label"
            />
            <span v-else v-html="link.label" />
        </Button>
    </nav>
</template>
