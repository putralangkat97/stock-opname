<script setup lang="ts">
import type { SidebarProps } from "@/components/ui/sidebar";
import { Link, usePage } from "@inertiajs/vue3";
import {
    HomeIcon,
    MoveDownLeftIcon,
    MoveUpRightIcon,
    ArrowRightLeftIcon,
    SlidersHorizontalIcon,
    ClipboardListIcon,
    PackageIcon,
    TagIcon,
    Ruler,
    WarehouseIcon,
    TruckIcon,
    UsersIcon,
    ScrollTextIcon,
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
        name: page.props.auth.user?.name ?? "User",
        email: page.props.auth.user?.email ?? "",
        avatar: "",
    },
    projects: [
        {
            name: "Dashboard",
            url: "/dashboard",
            icon: HomeIcon,
            isActive: page.component === "dashboard/index",
        },
    ],
    navMain: [
        {
            title: "Data Master",
            url: "#",
            items: [
                { title: "Produk", url: "/products", icon: PackageIcon },
                { title: "Kategori", url: "/categories", icon: TagIcon },
                { title: "Merek", url: "/brands", icon: TagIcon },
                { title: "Satuan", url: "/units", icon: Ruler },
                { title: "Gudang", url: "/warehouses", icon: WarehouseIcon },
                { title: "Pemasok", url: "/suppliers", icon: TruckIcon },
                { title: "Pelanggan", url: "/customers", icon: UsersIcon },
            ],
        },
        {
            title: "Transaksi Stok",
            url: "#",
            items: [
                {
                    title: "Penerimaan",
                    url: "/goods-receipts",
                    icon: MoveDownLeftIcon,
                },
                {
                    title: "Pengeluaran",
                    url: "/goods-issues",
                    icon: MoveUpRightIcon,
                },
                {
                    title: "Transfer Gudang",
                    url: "/warehouse-transfers",
                    icon: ArrowRightLeftIcon,
                },
                {
                    title: "Penyesuaian",
                    url: "/stock-adjustments",
                    icon: SlidersHorizontalIcon,
                },
                {
                    title: "Stok Opname",
                    url: "/stock-opnames",
                    icon: ClipboardListIcon,
                },
            ],
        },
        {
            title: "Administrasi",
            url: "#",
            items: [
                {
                    title: "Log Audit",
                    url: "/audit-logs",
                    icon: ScrollTextIcon,
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
                        <Link href="/dashboard" prefetch>
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
