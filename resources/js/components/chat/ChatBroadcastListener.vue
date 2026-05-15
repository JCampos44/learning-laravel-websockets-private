<script setup lang="ts">
import { useEcho } from '@laravel/echo-vue';
import { computed } from 'vue';
import type {
    ChatBroadcastConversationViewedPayload,
    ChatBroadcastMessageSentPayload,
} from '@/types';

const props = defineProps<{
    conversationId: number;
}>();

const emit = defineEmits<{
    (event: 'message-sent', payload: ChatBroadcastMessageSentPayload): void;
    (
        event: 'conversation-viewed',
        payload: ChatBroadcastConversationViewedPayload,
    ): void;
}>();

const channelName = computed(
    () => `chat.conversations.${props.conversationId}`,
);

useEcho<ChatBroadcastMessageSentPayload>(
    channelName.value,
    '.chat.message.sent',
    (payload) => {
        emit('message-sent', payload);
    },
    [channelName],
);

useEcho<ChatBroadcastConversationViewedPayload>(
    channelName.value,
    '.chat.conversation.viewed',
    (payload) => {
        emit('conversation-viewed', payload);
    },
    [channelName],
);
</script>

<template></template>
