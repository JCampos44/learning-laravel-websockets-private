<script setup lang="ts">
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import { BookOpen, FolderGit2, MessageCircleMore } from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
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
import { index as chatIndex } from '@/routes/chat';
import type { NavItem } from '@/types';

const page = usePage();

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
const chat = computed(() => page.props.chat);
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
            <ChatSidebarConversations
                v-if="isChatRoute && chat"
                :conversations="chat.conversations"
                :active-conversation-id="chat.activeConversationId"
            />
            <NavMain v-else :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
