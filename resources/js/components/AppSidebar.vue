<script setup lang="ts">
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import { BookOpen, FolderGit2, MessageCircleMore, Plus } from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import ChatBroadcastListener from '@/components/chat/ChatBroadcastListener.vue';
import ChatSidebarConversations from '@/components/chat/ChatSidebarConversations.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useChatState } from '@/composables/useChatState';
import { create as chatCreate, index as chatIndex } from '@/routes/chat';
import type { NavItem } from '@/types';

const page = usePage();
const { chat, handleMessageSent, handleConversationViewed } = useChatState(
    page.props.chat,
);
const currentUserId = computed(() => page.props.auth.user?.id ?? null);

const mainNavItems: NavItem[] = [
    {
        title: 'Chat',
        href: chatIndex(),
        icon: MessageCircleMore,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];

const isChatRoute = computed(() => page.url.startsWith('/chat'));

function onMessageSent(payload: Parameters<typeof handleMessageSent>[0]): void {
    handleMessageSent(payload, currentUserId.value);
}

function onConversationViewed(
    payload: Parameters<typeof handleConversationViewed>[0],
): void {
    handleConversationViewed(payload, currentUserId.value);
}
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" as-child>
                            <Link :href="chatIndex()">
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <template v-if="isChatRoute && chat">
                <ChatBroadcastListener
                    v-for="conversation in chat.conversations"
                    :key="conversation.id"
                    :conversation-id="conversation.id"
                    @message-sent="onMessageSent"
                    @conversation-viewed="onConversationViewed"
                />
            </template>
            <ChatSidebarConversations
                v-if="isChatRoute && chat"
                :conversations="chat.conversations"
                :active-conversation-id="chat.activeConversationId"
            />
            <SidebarMenu v-if="isChatRoute" class="px-2 pb-2">
                <SidebarMenuItem>
                    <SidebarMenuButton as-child class="h-auto py-2.5">
                        <Link :href="chatCreate()" prefetch>
                            <Plus class="size-4" />
                            <span>Nueva conversación</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <NavMain v-else :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
