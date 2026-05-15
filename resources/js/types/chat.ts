export type ChatConversation = {
    id: number;
    name: string;
    email: string;
    avatarInitials: string;
    avatarClass: string;
    lastMessage: string;
    lastMessageAt: string;
    lastMessageAtIso: string;
    unreadCount: number;
    isOnline: boolean;
    statusLabel: string;
};

export type ChatMessage = {
    id: number;
    author: string;
    body: string;
    sentAt: string;
    isMine: boolean;
    status: 'sent' | 'delivered' | 'read';
};

export type ChatBroadcastMessage = {
    id: number;
    conversation_id: number;
    sender_id: number;
    body: string;
    metadata: Record<string, unknown> | null;
    created_at: string | null;
    updated_at: string | null;
    sender: {
        id: number;
        name: string;
        email: string;
    };
};

export type ChatBroadcastMessageSentPayload = {
    message: ChatBroadcastMessage;
};

export type ChatBroadcastConversationViewedPayload = {
    conversation_id: number;
    participant_id: number;
    user_id: number;
    last_read_message_id: number | null;
    last_read_at: string | null;
};

export type ChatPageData = {
    conversations: ChatConversation[];
    activeConversationId: number | null;
    activeConversation: ChatConversation | null;
    messages: ChatMessage[];
    wasViewedOnServer: boolean;
};
