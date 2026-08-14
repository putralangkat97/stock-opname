<script lang="ts">
export const description = "An inset sidebar with secondary navigation.";
export const iframeHeight = "800px";
</script>

<script setup lang="ts">
import AppSidebar from "@/components/AppSidebar.vue";
import NotificationBell from "@/components/NotificationBell.vue";
import { Separator } from "@/components/ui/separator";
import {
    SidebarInset,
    SidebarProvider,
    SidebarTrigger,
} from "@/components/ui/sidebar";
import { Toaster } from "@/components/ui/sonner";
import Breadcrumb from "./partials/breadcrumb.vue";
import type { BreadcrumbEntry } from "@/types/ui";

defineProps<{
    breadcrumbs?: BreadcrumbEntry[] | undefined;
}>();
</script>

<template>
    <SidebarProvider>
        <AppSidebar />
        <SidebarInset>
            <header class="flex h-16 shrink-0 items-center gap-2">
                <div class="flex items-center gap-2 px-4">
                    <SidebarTrigger class="-ml-1" />
                    <Separator
                        orientation="vertical"
                        class="mr-2 data-[orientation=vertical]:h-4"
                    />
                </div>
                <Breadcrumb :breadcrumbs="breadcrumbs" />
                <div class="ml-auto flex items-center gap-2 px-4">
                    <NotificationBell />
                </div>
            </header>
            <div class="flex flex-1 flex-col gap-4 p-4 pt-0">
                <slot />
            </div>
        </SidebarInset>
    </SidebarProvider>

    <!-- Teleported straight to <body> — SidebarProvider/SidebarInset apply a
         CSS transform for the collapse/expand animation, which makes that
         ancestor the containing block for any position:fixed descendant.
         Without this Teleport, the Toaster gets visually clipped to the
         sidebar's box instead of positioning against the real viewport,
         which is exactly the "hidden behind the sidebar" symptom. -->
    <Teleport to="body">
        <Toaster position="top-right" rich-colors />
    </Teleport>
</template>
