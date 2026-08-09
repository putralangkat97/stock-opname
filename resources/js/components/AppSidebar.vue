<script setup lang="ts">
import type { SidebarProps } from "@/components/ui/sidebar";
import { usePage } from "@inertiajs/vue3";
import {
    HomeIcon,
    MoveDownLeftIcon,
    MoveUpRightIcon,
    ArrowRightLeftIcon,
    SlidersHorizontalIcon,
    CommandIcon,
} from "@lucide/vue";
import NavMain from "@/components/NavMain.vue";
import NavProjects from "@/components/NavProjects.vue";
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

const props = withDefaults(defineProps<SidebarProps>(), {
    variant: "inset",
});

const page = usePage();

const appName = import.meta.env.VITE_APP_NAME || "Stock Opname";

const data = {
    user: {
        name: "shadcn",
        email: "m@example.com",
        avatar: "/avatars/shadcn.jpg",
    },
    projects: [
        {
            name: "Dashboard",
            url: "#",
            icon: HomeIcon,
            isActive: page.component === "dashboard/index",
        },
    ],
    navMain: [
        {
            title: "Transaksi Stok",
            url: "#",
            items: [
                {
                    title: "Penerimaan",
                    url: "#",
                    icon: MoveDownLeftIcon,
                },
                {
                    title: "Pengeluaran",
                    url: "#",
                    icon: MoveUpRightIcon,
                },
                {
                    title: "Transfer Gudang",
                    url: "#",
                    icon: ArrowRightLeftIcon,
                },
                {
                    title: "Penyesuaian",
                    url: "#",
                    icon: SlidersHorizontalIcon,
                },
            ],
        },
    ],
};
</script>

<template>
    <Sidebar v-bind="props">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/dashboard">
                            <div
                                class="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground"
                            >
                                <CommandIcon class="size-4" />
                            </div>
                            <div
                                class="grid flex-1 text-left text-sm leading-tight"
                            >
                                <span class="truncate font-medium">
                                    {{ appName }}
                                </span>
                                <span class="truncate text-xs">Enterprise</span>
                            </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>
        <SidebarContent>
            <NavProjects :projects="data.projects" />
            <NavMain :items="data.navMain" />
        </SidebarContent>
        <SidebarFooter>
            <NavUser :user="data.user" />
        </SidebarFooter>
    </Sidebar>
</template>
