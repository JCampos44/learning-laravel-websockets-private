<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { MessageCircleMore } from 'lucide-vue-next';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import {
    Avatar,
    AvatarFallback,
} from '@/components/ui/avatar';
import {
    Badge,
} from '@/components/ui/badge';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { show as chatShow } from '@/routes/chat';
import type { ChatConversation } from '@/types';

defineProps<{
    conversations: ChatConversation[];
    activeConversationId: number | null;
}>();

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel class="flex items-center justify-between">
            <span class="flex items-center gap-2">
                <MessageCircleMore class="size-4 text-muted-foreground" />
                <span>Conversations</span>
            </span>
            <Badge variant="secondary" class="rounded-full px-2 py-0.5 text-[10px]">
                {{ conversations.length }}
            </Badge>
        </SidebarGroupLabel>
        <SidebarMenu class="space-y-1">
            <SidebarMenuItem v-for="conversation in conversations" :key="conversation.id">
                <SidebarMenuButton
                    as-child
                    :is-active="
                        activeConversationId === conversation.id
                            || isCurrentUrl(chatShow(conversation.id))
                    "
                    :tooltip="conversation.name"
                    class="h-auto py-2.5"
                >
                    <Link :href="chatShow(conversation.id)" prefetch>
                        <Avatar class="size-9 border border-sidebar-border/70">
                            <AvatarFallback :class="conversation.avatarClass">
                                {{ conversation.avatarInitials }}
                            </AvatarFallback>
                        </Avatar>

                        <span class="min-w-0 flex-1 text-left">
                            <span class="flex items-center gap-2">
                                <span class="truncate text-sm font-medium">
                                    {{ conversation.name }}
                                </span>
                                <span
                                    v-if="conversation.isOnline"
                                    class="size-2 rounded-full bg-emerald-500"
                                />
                            </span>
                            <span class="block truncate text-xs text-muted-foreground">
                                {{ conversation.lastMessage }}
                            </span>
                        </span>

                        <span class="ml-auto flex flex-col items-end gap-1 text-right">
                            <span class="text-[11px] text-muted-foreground">
                                {{ conversation.lastMessageAt }}
                            </span>
                            <Badge
                                v-if="conversation.unreadCount > 0"
                                variant="secondary"
                                class="h-5 rounded-full px-1.5 text-[10px]"
                            >
                                {{ conversation.unreadCount }}
                            </Badge>
                        </span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
