import { ref } from 'vue';
import { viewed as chatViewed } from '@/routes/chat';
import type {
    ChatBroadcastConversationViewedPayload,
    ChatBroadcastMessageSentPayload,
    ChatConversation,
    ChatMessage,
    ChatPageData,
} from '@/types';

const chatState = ref<ChatPageData | null>(null);
const pendingViewedConversationIds = new Set<number>();

function cloneConversation(conversation: ChatConversation): ChatConversation {
    return {
        ...conversation,
    };
}

function cloneMessage(message: ChatMessage): ChatMessage {
    return {
        ...message,
    };
}

function cloneChat(nextChat: ChatPageData): ChatPageData {
    const conversations = nextChat.conversations.map(cloneConversation);
    const activeConversation =
        nextChat.activeConversationId === null
            ? null
            : conversations.find(
                  (conversation) =>
                      conversation.id === nextChat.activeConversationId,
              ) ?? (nextChat.activeConversation ? cloneConversation(nextChat.activeConversation) : null);

    return {
        conversations,
        activeConversationId: nextChat.activeConversationId,
        activeConversation,
        messages: nextChat.messages.map(cloneMessage),
    };
}

function findConversation(
    conversationId: number,
): ChatConversation | null {
    return (
        chatState.value?.conversations.find(
            (conversation) => conversation.id === conversationId,
        ) ?? null
    );
}

function updateConversation(
    conversationId: number,
    callback: (conversation: ChatConversation) => void,
): void {
    if (!chatState.value) {
        return;
    }

    const conversation = findConversation(conversationId);

    if (conversation) {
        callback(conversation);
    }

    if (
        chatState.value.activeConversation !== null &&
        chatState.value.activeConversation.id === conversationId &&
        chatState.value.activeConversation !== conversation
    ) {
        callback(chatState.value.activeConversation);
    }
}

function formatBroadcastTimestamp(timestamp: string | null): string {
    if (!timestamp) {
        return 'Ahora';
    }

    return new Intl.DateTimeFormat('es-CL', {
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(timestamp));
}

function getXsrfToken(): string | null {
    if (typeof document === 'undefined') {
        return null;
    }

    const token = document.cookie
        .split('; ')
        .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
        ?.split('=')
        .at(1);

    return token ? decodeURIComponent(token) : null;
}

async function markConversationViewed(conversationId: number): Promise<void> {
    if (
        typeof window === 'undefined' ||
        pendingViewedConversationIds.has(conversationId)
    ) {
        return;
    }

    const xsrfToken = getXsrfToken();

    if (!xsrfToken) {
        return;
    }

    pendingViewedConversationIds.add(conversationId);

    try {
        await fetch(chatViewed(conversationId).url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken,
            },
        });
    } finally {
        pendingViewedConversationIds.delete(conversationId);
    }
}

export function useChatState(initialChat?: ChatPageData) {
    if (initialChat) {
        chatState.value = cloneChat(initialChat);
    }

    function syncChat(nextChat: ChatPageData): void {
        chatState.value = cloneChat(nextChat);
    }

    function handleMessageSent(
        payload: ChatBroadcastMessageSentPayload,
        currentUserId: number | null,
    ): void {
        if (!chatState.value) {
            return;
        }

        const message = payload.message;
        const isMine = message.sender_id === currentUserId;
        const isActiveConversation =
            chatState.value.activeConversationId === message.conversation_id;

        updateConversation(message.conversation_id, (conversation) => {
            conversation.lastMessage = message.body;
            conversation.lastMessageAt = formatBroadcastTimestamp(message.created_at);
            conversation.unreadCount =
                isActiveConversation || isMine
                    ? 0
                    : conversation.unreadCount + 1;
        });

        if (
            isActiveConversation &&
            !chatState.value.messages.some(
                (existingMessage) => existingMessage.id === message.id,
            )
        ) {
            chatState.value.messages.push({
                id: message.id,
                author: message.sender.name,
                body: message.body,
                sentAt: formatBroadcastTimestamp(message.created_at),
                isMine,
                status: isMine ? 'sent' : 'delivered',
            });

            if (!isMine) {
                void markConversationViewed(message.conversation_id);
            }
        }
    }

    function handleConversationViewed(
        payload: ChatBroadcastConversationViewedPayload,
        currentUserId: number | null,
    ): void {
        if (!chatState.value) {
            return;
        }

        if (payload.user_id === currentUserId) {
            return;
        }

        if (chatState.value.activeConversationId !== payload.conversation_id) {
            return;
        }

        const lastReadMessageId = payload.last_read_message_id;

        if (lastReadMessageId === null) {
            return;
        }

        chatState.value.messages.forEach((message) => {
            if (message.isMine && message.id <= lastReadMessageId) {
                message.status = 'read';
            }
        });
    }

    return {
        chat: chatState,
        syncChat,
        handleMessageSent,
        handleConversationViewed,
    };
}
