<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { MessageSquareText, Send } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useChatState } from '@/composables/useChatState';
import { store as chatMessagesStore } from '@/routes/chat/messages';
import type { ChatPageData } from '@/types';

const props = defineProps<{
    chat: ChatPageData;
}>();

const draft = ref('');
const messagesScrollContainer = ref<HTMLElement | null>(null);
const { chat, syncChat, ensureConversationViewed } = useChatState(props.chat);
const activeConversation = computed(() => chat.value?.activeConversation ?? null);

watch(
    () => props.chat,
    (nextChat) => {
        syncChat(nextChat);
    },
    { immediate: true },
);

watch(
    () => activeConversation.value?.id ?? null,
    (conversationId) => {
        ensureConversationViewed(
            conversationId,
            chat.value?.wasViewedOnServer ?? false,
        );
    },
    { immediate: true },
);

const pageTitle = computed(() =>
    activeConversation.value ? `${activeConversation.value.name} · Chat` : 'Chat',
);

function scrollMessagesToBottom(): void {
    nextTick(() => {
        messagesScrollContainer.value?.scrollTo({
            top: messagesScrollContainer.value.scrollHeight,
            behavior: 'smooth',
        });
    });
}

function sendMessage(): void {
    if (!activeConversation.value) {
        return;
    }

    const trimmedDraft = draft.value.trim();

    if (!trimmedDraft) {
        return;
    }

    router.post(
        chatMessagesStore(activeConversation.value.id).url,
        {
            body: trimmedDraft,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                draft.value = '';
            },
        },
    );
}

function handleDraftKeydown(event: KeyboardEvent): void {
    if (event.key !== 'Enter' || event.shiftKey || event.isComposing) {
        return;
    }

    event.preventDefault();
    sendMessage();
}

watch(
    () => chat.value?.messages.length ?? 0,
    () => {
        scrollMessagesToBottom();
    },
    { flush: 'post' },
);
</script>

<template>
    <Head :title="pageTitle" />

    <div class="flex h-full min-h-0 flex-1 overflow-hidden">
        <div
            class="flex h-full min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-sidebar-border/70 bg-background/95 shadow-[0_18px_60px_rgba(15,23,42,0.08)] dark:border-sidebar-border"
        >
            <template v-if="activeConversation">
                <header
                    class="flex shrink-0 items-center gap-4 border-b border-sidebar-border/70 px-6 py-4"
                >
                    <Avatar class="size-12 border border-sidebar-border/70 shadow-sm">
                        <AvatarFallback :class="activeConversation.avatarClass">
                            {{ activeConversation.avatarInitials }}
                        </AvatarFallback>
                    </Avatar>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-3">
                            <h1 class="truncate text-lg font-semibold">
                                {{ activeConversation.name }}
                            </h1>
                            <Badge variant="secondary" class="rounded-full px-2.5 py-0.5">
                                {{ activeConversation.statusLabel }}
                            </Badge>
                        </div>
                        <p class="truncate text-sm text-muted-foreground">
                            {{ activeConversation.email }} · Último mensaje
                            {{ activeConversation.lastMessageAt }}
                        </p>
                    </div>
                </header>

                <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                    <div
                        ref="messagesScrollContainer"
                        class="flex-1 overflow-y-auto px-6 py-6"
                    >
                        <div class="mx-auto flex w-full max-w-4xl flex-col gap-4">
                            <div
                                v-for="message in chat?.messages ?? []"
                                :key="message.id"
                                class="flex"
                                :class="message.isMine ? 'justify-end' : 'justify-start'"
                            >
                                <div class="max-w-[min(44rem,82%)]">
                                    <div
                                        class="rounded-3xl px-4 py-3 shadow-sm"
                                        :class="
                                            message.isMine
                                                ? 'rounded-br-md bg-primary text-primary-foreground'
                                                : 'rounded-bl-md border border-sidebar-border/70 bg-muted/60 text-foreground dark:bg-muted/40'
                                        "
                                    >
                                        <p class="whitespace-pre-wrap text-sm leading-6">
                                            {{ message.body }}
                                        </p>
                                    </div>

                                    <div
                                        class="mt-1 flex items-center gap-2 text-xs text-muted-foreground"
                                        :class="message.isMine ? 'justify-end' : 'justify-start'"
                                    >
                                        <span>{{ message.sentAt }}</span>
                                        <span v-if="message.isMine">
                                            · {{ message.status === 'read' ? 'Leído' : 'Enviado' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form
                        class="shrink-0 border-t border-sidebar-border/70 bg-background/95 px-6 py-4"
                        @submit.prevent="sendMessage"
                    >
                        <div
                            class="mx-auto flex w-full max-w-4xl items-end gap-3 rounded-3xl border border-sidebar-border/70 bg-background p-3 shadow-sm"
                        >
                            <label class="sr-only" for="chat-draft">Mensaje</label>
                            <textarea
                                id="chat-draft"
                                v-model="draft"
                                rows="2"
                                :disabled="!activeConversation"
                                @keydown="handleDraftKeydown"
                                class="min-h-[3.5rem] flex-1 resize-none border-0 bg-transparent px-1 py-2 text-sm shadow-none outline-none placeholder:text-muted-foreground focus-visible:ring-0 disabled:cursor-not-allowed"
                                :placeholder="
                                    activeConversation
                                        ? 'Escribe un mensaje...'
                                        : 'Selecciona una conversación para escribir'
                                "
                            />
                            <Button
                                type="submit"
                                class="rounded-2xl px-4"
                                :disabled="!activeConversation || draft.trim().length === 0"
                            >
                                <Send class="size-4" />
                                <span>Enviar</span>
                            </Button>
                        </div>

                        <p
                            v-if="activeConversation"
                            class="mx-auto mt-2 max-w-4xl text-xs text-muted-foreground"
                        >
                            Ahora el formulario pega al backend y el panel se actualiza por
                            Echo.
                        </p>
                    </form>
                </div>
            </template>

            <template v-else>
                <div class="flex h-full min-h-0 items-center justify-center px-6 py-10">
                    <div
                        class="max-w-xl rounded-3xl border border-sidebar-border/70 bg-background/95 p-8 text-center shadow-sm"
                    >
                        <div
                            class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-primary/10 text-primary"
                        >
                            <MessageSquareText class="size-7" />
                        </div>
                        <h1 class="text-2xl font-semibold tracking-tight">
                            Selecciona una conversación
                        </h1>
                        <p class="mt-3 text-sm leading-6 text-muted-foreground">
                            El sidebar ya muestra una lista simulada de conversaciones.
                            Al elegir una, el panel principal se convierte en el chat
                            completo con el formulario fijo al fondo.
                        </p>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>
