export type ChatConversation = {
    id: number;
    name: string;
    email: string;
    avatarInitials: string;
    avatarClass: string;
    lastMessage: string;
    lastMessageAt: string;
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

export type ChatPageData = {
    conversations: ChatConversation[];
    activeConversationId: number | null;
    activeConversation: ChatConversation | null;
    messages: ChatMessage[];
};
