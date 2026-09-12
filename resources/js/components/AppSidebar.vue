<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import {
    Book,
    BookOpen,
    FolderGit2,
    FolderTree,
    GraduationCap,
    LayoutGrid,
    PencilRuler,
} from "@lucide/vue";
import AppLogo from "@/components/AppLogo.vue";
import NavFooter from "@/components/NavFooter.vue";
import NavMain from "@/components/NavMain.vue";
import NavUser from "@/components/NavUser.vue";
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from "@/components/ui/sidebar";
import { dashboard } from "@/routes";
import { index as categoriesIndex } from "@/routes/categories";
import { index as coursesIndex } from "@/routes/courses";
import { index as enrollmentsIndex } from "@/routes/enrollments";
import { index as instructorCoursesIndex } from "@/routes/instructor/courses";
import type { NavItem } from "@/types";

const mainNavItems: NavItem[] = [
    {
        title: "Dashboard",
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: "Courses",
        href: coursesIndex(),
        icon: Book,
    },
    {
        title: "Categories",
        href: categoriesIndex(),
        icon: FolderTree,
    },
    {
        title: "My enrollments",
        href: enrollmentsIndex(),
        icon: GraduationCap,
    },
    {
        title: "Teaching",
        href: instructorCoursesIndex(),
        icon: PencilRuler,
    },
];

const footerNavItems: NavItem[] = [
    // {
    //     title: 'Repository',
    //     href: 'https://github.com/laravel/vue-starter-kit',
    //     icon: FolderGit2,
    // },
    // {
    //     title: 'Documentation',
    //     href: 'https://laravel.com/docs/starter-kits#vue',
    //     icon: BookOpen,
    // },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
