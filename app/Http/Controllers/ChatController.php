<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('chat/Index', [
            'chat' => $this->chatData(),
        ]);
    }

    public function show(int $conversation): Response
    {
        return Inertia::render('chat/Index', [
            'chat' => $this->chatData($conversation),
        ]);
    }

    /**
     * @return array{
     *     conversations: array<int, array<string, mixed>>,
     *     activeConversationId: int|null,
     *     activeConversation: array<string, mixed>|null,
     *     messages: array<int, array<string, mixed>>
     * }
     */
    private function chatData(?int $activeConversationId = null): array
    {
        $conversations = $this->conversations();
        $activeConversation = null;

        foreach ($conversations as $conversation) {
            if ($conversation['id'] === $activeConversationId) {
                $activeConversation = $conversation;

                break;
            }
        }

        if ($activeConversationId !== null && $activeConversation === null) {
            abort(404);
        }

        return [
            'conversations' => $conversations,
            'activeConversationId' => $activeConversation['id'] ?? null,
            'activeConversation' => $activeConversation,
            'messages' => $activeConversation !== null
                ? $this->messagesForConversation($activeConversation['id'])
                : [],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function conversations(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Ana Morales',
                'email' => 'ana.morales@example.com',
                'avatarInitials' => 'AM',
                'avatarClass' => 'bg-emerald-500/15 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200',
                'lastMessage' => 'Te comparto el esquema final esta tarde.',
                'lastMessageAt' => '10:42',
                'unreadCount' => 2,
                'isOnline' => true,
                'statusLabel' => 'En línea',
            ],
            [
                'id' => 2,
                'name' => 'Carlos Vega',
                'email' => 'carlos.vega@example.com',
                'avatarInitials' => 'CV',
                'avatarClass' => 'bg-sky-500/15 text-sky-700 dark:bg-sky-500/20 dark:text-sky-200',
                'lastMessage' => 'Perfecto, lo reviso en cuanto termine el build.',
                'lastMessageAt' => '09:18',
                'unreadCount' => 0,
                'isOnline' => false,
                'statusLabel' => 'Visto hace 8 min',
            ],
            [
                'id' => 3,
                'name' => 'Lucía Torres',
                'email' => 'lucia.torres@example.com',
                'avatarInitials' => 'LT',
                'avatarClass' => 'bg-violet-500/15 text-violet-700 dark:bg-violet-500/20 dark:text-violet-200',
                'lastMessage' => '¿Probaste el nuevo layout del chat?',
                'lastMessageAt' => '08:07',
                'unreadCount' => 4,
                'isOnline' => true,
                'statusLabel' => 'Escribiendo',
            ],
            [
                'id' => 4,
                'name' => 'Mateo Ríos',
                'email' => 'mateo.rios@example.com',
                'avatarInitials' => 'MR',
                'avatarClass' => 'bg-amber-500/15 text-amber-700 dark:bg-amber-500/20 dark:text-amber-200',
                'lastMessage' => 'Subí la rama con los cambios del sidebar.',
                'lastMessageAt' => 'Ayer',
                'unreadCount' => 1,
                'isOnline' => false,
                'statusLabel' => 'Disponible mañana',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function messagesForConversation(int $conversationId): array
    {
        return match ($conversationId) {
            1 => [
                [
                    'id' => 101,
                    'author' => 'Ana Morales',
                    'body' => 'Ya dejé listo el resumen de la migración.',
                    'sentAt' => '09:51',
                    'isMine' => false,
                    'status' => 'read',
                ],
                [
                    'id' => 102,
                    'author' => 'Tú',
                    'body' => 'Perfecto. Quiero que la vista principal se vea más limpia antes de conectar el backend.',
                    'sentAt' => '09:54',
                    'isMine' => true,
                    'status' => 'read',
                ],
                [
                    'id' => 103,
                    'author' => 'Ana Morales',
                    'body' => 'Entonces arranco con el sidebar y la conversación simulada para que quede bien claro el flujo.',
                    'sentAt' => '10:42',
                    'isMine' => false,
                    'status' => 'delivered',
                ],
            ],
            2 => [
                [
                    'id' => 201,
                    'author' => 'Carlos Vega',
                    'body' => 'Estoy validando el layout en mobile y desktop para asegurar que el panel ocupe todo el espacio.',
                    'sentAt' => '08:48',
                    'isMine' => false,
                    'status' => 'read',
                ],
                [
                    'id' => 202,
                    'author' => 'Tú',
                    'body' => 'Buena idea. Si el sidebar colapsa bien, la experiencia se siente más natural.',
                    'sentAt' => '08:55',
                    'isMine' => true,
                    'status' => 'read',
                ],
                [
                    'id' => 203,
                    'author' => 'Carlos Vega',
                    'body' => 'Sí, además deja listo el espacio para cuando conectemos el websocket real.',
                    'sentAt' => '09:18',
                    'isMine' => false,
                    'status' => 'delivered',
                ],
            ],
            3 => [
                [
                    'id' => 301,
                    'author' => 'Lucía Torres',
                    'body' => 'El nuevo diseño del chat ya se entiende mucho mejor.',
                    'sentAt' => '07:32',
                    'isMine' => false,
                    'status' => 'read',
                ],
                [
                    'id' => 302,
                    'author' => 'Tú',
                    'body' => 'Quise dejar el composer abajo y el historial arriba para que el flujo fuera obvio.',
                    'sentAt' => '07:39',
                    'isMine' => true,
                    'status' => 'read',
                ],
                [
                    'id' => 303,
                    'author' => 'Lucía Torres',
                    'body' => 'Eso ayuda mucho. Ahora ya podemos reemplazar la lista simulada por datos reales cuando toque.',
                    'sentAt' => '08:07',
                    'isMine' => false,
                    'status' => 'delivered',
                ],
            ],
            4 => [
                [
                    'id' => 401,
                    'author' => 'Mateo Ríos',
                    'body' => 'Dejé el backend limpio para que luego solo conectes la interfaz.',
                    'sentAt' => 'Ayer',
                    'isMine' => false,
                    'status' => 'read',
                ],
                [
                    'id' => 402,
                    'author' => 'Tú',
                    'body' => 'Perfecto, así el frontend puede crecer sin tocar la estructura de conversación.',
                    'sentAt' => 'Ayer',
                    'isMine' => true,
                    'status' => 'delivered',
                ],
            ],
            default => [],
        };
    }
}
