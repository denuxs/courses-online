<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import FeaturedCourses from "@/components/landing/FeaturedCourses.vue";
import FeaturesSection from "@/components/landing/FeaturesSection.vue";
import HeroSection from "@/components/landing/HeroSection.vue";
import HowItWorksSection from "@/components/landing/HowItWorksSection.vue";
import LandingFooter from "@/components/landing/LandingFooter.vue";
import LandingHeader from "@/components/landing/LandingHeader.vue";
import StatsSection from "@/components/landing/StatsSection.vue";
import TestimonialsSection from "@/components/landing/TestimonialsSection.vue";
import type { Course, LandingStats } from "@/types";

defineProps<{
    featured_courses: Course[];
    stats: LandingStats;
}>();
</script>

<template>
    <Head title="Inicio" />

    <div class="min-h-screen bg-background text-foreground">
        <LandingHeader />

        <main>
            <HeroSection />
            <StatsSection :stats="stats" />
            <FeaturesSection />
            <FeaturedCourses :courses="featured_courses" />
            <HowItWorksSection />
            <TestimonialsSection />
        </main>

        <LandingFooter />
    </div>
</template>
