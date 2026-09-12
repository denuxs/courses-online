<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import type { Component } from "vue";
import { computed } from "vue";
import { Card, CardContent } from "@/components/ui/card";

type Props = {
    label: string;
    value: number;
    icon?: Component;
    href?: string | { url: string };
    format?: "number" | "currency";
};

const props = withDefaults(defineProps<Props>(), {
    format: "number",
});

const formatted = computed(() =>
    props.format === "currency"
        ? new Intl.NumberFormat("es", {
              style: "currency",
              currency: "USD",
          }).format(props.value)
        : new Intl.NumberFormat("es").format(props.value),
);
</script>

<template>
    <component :is="href ? Link : 'div'" :href="href" class="block">
        <Card :class="href ? 'transition-colors hover:bg-muted/50' : ''">
            <CardContent class="flex items-center justify-between gap-4 pt-6">
                <div>
                    <p class="text-sm text-muted-foreground">{{ label }}</p>
                    <p class="mt-1 text-3xl font-bold text-foreground">
                        {{ formatted }}
                    </p>
                </div>
                <component
                    :is="icon"
                    v-if="icon"
                    class="size-8 text-muted-foreground"
                />
            </CardContent>
        </Card>
    </component>
</template>
