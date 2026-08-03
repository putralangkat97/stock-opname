<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import type { LucideIcon } from "@lucide/vue";
import {
    SidebarGroup,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from "@/components/ui/sidebar";

defineProps<{
    projects: {
        name: string;
        url: string;
        icon: LucideIcon;
        isActive?: boolean;
    }[];
}>();

const { isMobile } = useSidebar();
</script>

<template>
    <SidebarGroup class="group-data-[collapsible=icon]:hidden">
        <SidebarMenu>
            <SidebarMenuItem v-for="item in projects" :key="item.name">
                <SidebarMenuButton as-child :is-active="item.isActive">
                    <Link :href="item.url">
                        <component :is="item.icon" />
                        <span>{{ item.name }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
