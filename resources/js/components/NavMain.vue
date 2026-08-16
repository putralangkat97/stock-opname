<script setup lang="ts">
import type { LucideIcon } from "@lucide/vue";
import { ChevronRight } from "@lucide/vue";
import { Link, usePage } from "@inertiajs/vue3";
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from "@/components/ui/collapsible";
import {
    SidebarGroup,
    SidebarMenu,
    SidebarMenuAction,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from "@/components/ui/sidebar";

defineProps<{
    items: {
        title: string;
        url: string;
        icon?: LucideIcon;
        isActive?: boolean;
        items?: {
            title: string;
            url: string;
            icon?: LucideIcon;
        }[];
    }[];
}>();

const page = usePage();

// Sub-items always carry a real route (e.g. "/products"); highlight one as
// active if the current path starts with it, so nested show/edit routes
// like "/products/5" still light up "Produk" in the nav.
function isSubItemActive(url: string): boolean {
    return url !== "#" && page.url.startsWith(url);
}
</script>

<template>
    <SidebarGroup>
        <!-- <SidebarGroupLabel>Platform</SidebarGroupLabel> -->
        <SidebarMenu>
            <Collapsible
                v-for="item in items"
                :key="item.title"
                as-child
                :default-open="true"
            >
                <SidebarMenuItem>
                    <!-- Top-level items here are always group headers (url="#"),
                         not real pages — render as a non-navigating button so
                         clicking never tries to visit "#" through Inertia. -->
                    <SidebarMenuButton
                        v-if="!item.items?.length"
                        as-child
                        :tooltip="item.title"
                    >
                        <Link :href="item.url" prefetch>
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                    <SidebarMenuButton v-else :tooltip="item.title">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </SidebarMenuButton>

                    <template v-if="item.items?.length">
                        <CollapsibleTrigger as-child>
                            <SidebarMenuAction
                                class="data-[state=open]:rotate-90"
                            >
                                <ChevronRight />
                                <span class="sr-only">Toggle</span>
                            </SidebarMenuAction>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <SidebarMenuSubItem
                                    v-for="subItem in item.items"
                                    :key="subItem.title"
                                >
                                    <SidebarMenuSubButton
                                        as-child
                                        :is-active="
                                            isSubItemActive(subItem.url)
                                        "
                                    >
                                        <Link :href="subItem.url" prefetch>
                                            <component :is="subItem.icon" />
                                            <span>{{ subItem.title }}</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </template>
                </SidebarMenuItem>
            </Collapsible>
        </SidebarMenu>
    </SidebarGroup>
</template>
