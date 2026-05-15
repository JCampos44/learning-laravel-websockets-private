<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, MessageCirclePlus, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useChatState } from '@/composables/useChatState';
import { store as chatConversationsStore } from '@/routes/chat/conversations';
import { show as chatShow } from '@/routes/chat';
import type { ChatPageData } from '@/types';

type ChatContact = {
    id: number;
    name: string;
    email: string;
    avatarInitials: string;
    avatarClass: string;
    existingConversationId: number | null;
};

const props = defineProps<{
    chat: ChatPageData;
    contacts: ChatContact[];
}>();

useChatState(props.chat);
const search = ref('');
const pendingContactId = ref<number | null>(null);

const filteredContacts = computed(() => {
    const query = search.value.trim().toLowerCase();

    if (!query) {
        return props.contacts;
    }

    return props.contacts.filter((contact) => {
        return (
            contact.name.toLowerCase().includes(query) ||
            contact.email.toLowerCase().includes(query)
        );
    });
});

function startConversation(contactId: number): void {
    pendingContactId.value = contactId;

    router.post(
        chatConversationsStore().url,
        {
            participant_id: contactId,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                pendingContactId.value = null;
            },
        },
    );
}
</script>

<template>
    <Head title="Nueva conversación" />

    <div class="flex h-full min-h-0 flex-1 overflow-hidden">
        <div
            class="flex h-full min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-sidebar-border/70 bg-background/95 shadow-[0_18px_60px_rgba(15,23,42,0.08)] dark:border-sidebar-border"
        >
            <header class="border-b border-sidebar-border/70 px-6 py-5">
                <div class="flex items-start gap-4">
                    <div
                        class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary"
                    >
                        <MessageCirclePlus class="size-6" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-3">
                            <h1 class="text-xl font-semibold tracking-tight">
                                Nueva conversación
                            </h1>
                            <Badge variant="secondary" class="rounded-full px-2.5 py-0.5">
                                {{ filteredContacts.length }} contactos
                            </Badge>
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Elige una persona para abrir un chat privado. Si ya existe una
                            conversación, te llevo directo al hilo.
                        </p>
                    </div>
                </div>
            </header>

            <div class="flex min-h-0 flex-1 flex-col gap-6 overflow-hidden p-6">
                <div class="max-w-xl">
                    <label class="sr-only" for="contact-search">Buscar contacto</label>
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            id="contact-search"
                            v-model="search"
                            class="pl-9"
                            placeholder="Buscar por nombre o correo"
                        />
                    </div>
                </div>

                <div
                    v-if="filteredContacts.length > 0"
                    class="grid gap-4 xl:grid-cols-3"
                >
                    <Card
                        v-for="contact in filteredContacts"
                        :key="contact.id"
                        class="border-sidebar-border/70 bg-background/95"
                    >
                        <CardHeader class="flex-row items-center gap-4 space-y-0">
                            <Avatar class="size-12 border border-sidebar-border/70">
                                <AvatarFallback :class="contact.avatarClass">
                                    {{ contact.avatarInitials }}
                                </AvatarFallback>
                            </Avatar>

                            <div class="min-w-0 flex-1">
                                <CardTitle class="truncate text-base">
                                    {{ contact.name }}
                                </CardTitle>
                                <CardDescription class="truncate">
                                    {{ contact.email }}
                                </CardDescription>
                            </div>

                            <Badge
                                v-if="contact.existingConversationId !== null"
                                variant="secondary"
                                class="rounded-full px-2.5 py-0.5"
                            >
                                Existente
                            </Badge>
                        </CardHeader>

                        <CardContent class="flex items-center justify-between gap-4">
                            <p class="text-sm text-muted-foreground">
                                {{
                                    contact.existingConversationId !== null
                                        ? 'Ya tienes un hilo con esta persona.'
                                        : 'Comienza una conversación privada desde cero.'
                                }}
                            </p>

                            <Button
                                v-if="contact.existingConversationId !== null"
                                as-child
                                class="rounded-2xl"
                            >
                                <Link :href="chatShow(contact.existingConversationId)">
                                    Abrir
                                    <ArrowRight class="size-4" />
                                </Link>
                            </Button>

                            <Button
                                v-else
                                class="rounded-2xl"
                                :disabled="pendingContactId === contact.id"
                                @click="startConversation(contact.id)"
                            >
                                {{
                                    pendingContactId === contact.id
                                        ? 'Abriendo...'
                                        : 'Iniciar'
                                }}
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <div
                    v-else
                    class="flex flex-1 items-center justify-center rounded-3xl border border-dashed border-sidebar-border/70 bg-background/60 px-6 py-10"
                >
                    <div class="max-w-md text-center">
                        <p class="text-lg font-medium">
                            No hay contactos que coincidan con tu búsqueda.
                        </p>
                        <p class="mt-2 text-sm text-muted-foreground">
                            Cambia el texto o limpia el filtro para ver todos los usuarios
                            disponibles.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
