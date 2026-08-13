<script setup lang="ts">
import { router, usePage } from "@inertiajs/vue3";
import { BellIcon } from "@lucide/vue";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuItem,
} from "@/components/ui/dropdown-menu";

const page = usePage();

// notifications is shared globally via HandleInertiaRequests, so this
// component works on every page without fetching anything itself.
const notifications = () => page.props.notifications;

function openNotification(id: string, link: string | null) {
    router.post(
        `/notifications/${id}/read`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                if (link) router.visit(link);
            },
        },
    );
}

function markAllAsRead() {
    router.post("/notifications/read-all", {}, { preserveScroll: true });
}
</script>

<template>
    <DropdownMenu v-if="notifications()">
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="relative">
                <BellIcon class="size-5" />
                <Badge
                    v-if="notifications()!.unreadCount > 0"
                    variant="destructive"
                    class="absolute -top-1 -right-1 h-4 min-w-4 justify-center rounded-full px-1 text-[10px]"
                >
                    {{
                        notifications()!.unreadCount > 9
                            ? "9+"
                            : notifications()!.unreadCount
                    }}
                </Badge>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-80">
            <div class="flex items-center justify-between px-2 py-1.5">
                <DropdownMenuLabel class="p-0">Notifications</DropdownMenuLabel>
                <Button
                    v-if="notifications()!.unreadCount > 0"
                    variant="ghost"
                    size="sm"
                    class="h-auto p-0 text-xs text-muted-foreground hover:text-foreground"
                    @click="markAllAsRead"
                >
                    Mark all as read
                </Button>
            </div>
            <DropdownMenuSeparator />

            <p
                v-if="notifications()!.recent.length === 0"
                class="px-2 py-4 text-center text-sm text-muted-foreground"
            >
                No notifications yet.
            </p>

            <DropdownMenuItem
                v-for="n in notifications()!.recent"
                :key="n.id"
                class="flex flex-col items-start gap-0.5 whitespace-normal py-2"
                :class="!n.read && 'bg-accent/50'"
                @click="openNotification(n.id, n.link)"
            >
                <div class="flex w-full items-center gap-2">
                    <span class="text-sm font-medium">{{ n.title }}</span>
                    <span
                        v-if="!n.read"
                        class="ml-auto size-1.5 shrink-0 rounded-full bg-primary"
                    />
                </div>
                <span class="text-xs text-muted-foreground">
                    {{ n.message }}
                </span>
                <span class="text-xs text-muted-foreground">
                    {{ n.created_at }}
                </span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
