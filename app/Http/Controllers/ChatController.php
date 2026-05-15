<?php

namespace App\Http\Controllers;

use App\Events\Chat\ConversationViewed;
use App\Events\Chat\MessageSent;
use App\Http\Requests\StoreConversationRequest;
use App\Http\Requests\StoreMessageRequest;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    /**
     * Show the authenticated user's chat inbox.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('chat/Index', [
            'chat' => $this->chatData($request->user()),
        ]);
    }

    /**
     * Show the conversation creation screen.
     */
    public function create(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('chat/Create', [
            'chat' => $this->chatData($user),
            'contacts' => $this->availableContacts($user),
        ]);
    }

    /**
     * Show a conversation and mark it as viewed.
     */
    public function show(Request $request, Conversation $conversation): Response
    {
        $user = $request->user();
        $conversation = $this->loadVisibleConversation($user, $conversation);

        $this->markConversationAsViewed($conversation, $user);

        return Inertia::render('chat/Index', [
            'chat' => $this->chatData($user, $conversation->id),
        ]);
    }

    /**
     * Persist a new message in the conversation.
     */
    public function store(StoreMessageRequest $request, Conversation $conversation): RedirectResponse
    {
        $user = $request->user();
        $conversation = $this->loadVisibleConversation($user, $conversation);

        $message = DB::transaction(function () use ($conversation, $user, $request): Message {
            $message = $conversation->messages()->create([
                'sender_id' => $user->id,
                'body' => $request->validated('body'),
                'metadata' => ['kind' => 'text'],
            ]);

            $conversation->forceFill([
                'last_message_id' => $message->id,
                'last_message_at' => $message->created_at,
            ])->save();

            return $message;
        });

        MessageSent::dispatch($message->loadMissing('conversation', 'sender'));

        return to_route('chat.show', $conversation);
    }

    /**
     * Mark a conversation as viewed without navigating away from the current page.
     */
    public function view(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        $conversation = $this->loadVisibleConversation($user, $conversation);

        $this->markConversationAsViewed($conversation, $user);

        return response()->noContent();
    }

    /**
     * Create or open a direct conversation with another user.
     */
    public function storeConversation(StoreConversationRequest $request): RedirectResponse
    {
        $user = $request->user();
        $participant = User::query()->findOrFail($request->participantId());

        $conversation = DB::transaction(function () use ($user, $participant): Conversation {
            $conversation = Conversation::query()->firstOrCreate(
                [
                    'direct_key' => $this->directConversationKey($user->id, $participant->id),
                ],
                [
                    'type' => 'direct',
                    'title' => null,
                    'created_by_user_id' => $user->id,
                ],
            );

            ConversationParticipant::query()->firstOrCreate(
                [
                    'conversation_id' => $conversation->id,
                    'user_id' => $user->id,
                ],
                [
                    'role' => 'owner',
                    'joined_at' => now(),
                    'left_at' => null,
                    'last_read_at' => null,
                    'last_read_message_id' => null,
                    'archived_at' => null,
                    'muted_until' => null,
                ],
            );

            ConversationParticipant::query()->firstOrCreate(
                [
                    'conversation_id' => $conversation->id,
                    'user_id' => $participant->id,
                ],
                [
                    'role' => 'member',
                    'joined_at' => now(),
                    'left_at' => null,
                    'last_read_at' => null,
                    'last_read_message_id' => null,
                    'archived_at' => null,
                    'muted_until' => null,
                ],
            );

            return $conversation;
        });

        return to_route('chat.show', $conversation);
    }

    /**
     * Build the chat payload for the Inertia page.
     *
     * @return array{
     *     conversations: array<int, array<string, mixed>>,
     *     activeConversationId: int|null,
     *     activeConversation: array<string, mixed>|null,
     *     messages: array<int, array<string, mixed>>
     * }
     */
    private function chatData(User $user, ?int $activeConversationId = null): array
    {
        $conversations = $this->conversationListQuery($user)->get();
        $activeConversation = $activeConversationId !== null
            ? $this->loadConversationDetails($user, $activeConversationId)
            : null;

        return [
            'conversations' => $conversations
                ->map(fn (Conversation $conversation): array => $this->conversationSummary($conversation, $user))
                ->all(),
            'activeConversationId' => $activeConversation?->id,
            'activeConversation' => $activeConversation !== null
                ? $this->conversationSummary($activeConversation, $user)
                : null,
            'messages' => $activeConversation !== null
                ? $this->messageSummaries($activeConversation, $user)
                : [],
        ];
    }

    /**
     * Get the list query for the user's conversations.
     */
    private function conversationListQuery(User $user): Builder
    {
        return Conversation::query()
            ->whereHas('participantRecords', function (Builder $query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->whereNull('left_at');
            })
            ->with([
                'lastMessage.sender',
                'participants',
                'participantRecords' => function ($query) use ($user): void {
                    $query->where('user_id', $user->id);
                },
            ])
            ->orderByRaw('COALESCE(last_message_at, conversations.created_at) DESC')
            ->orderByDesc('conversations.id');
    }

    /**
     * Get contact suggestions for creating a direct conversation.
     *
     * @return array<int, array<string, mixed>>
     */
    private function availableContacts(User $user): array
    {
        $existingConversationIds = Conversation::query()
            ->where('type', 'direct')
            ->whereHas('participantRecords', function (Builder $query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->whereNull('left_at');
            })
            ->with('participantRecords')
            ->get()
            ->mapWithKeys(function (Conversation $conversation) use ($user): array {
                $otherParticipant = $conversation->participantRecords
                    ->first(fn (ConversationParticipant $participant): bool => $participant->user_id !== $user->id);

                if ($otherParticipant === null) {
                    return [];
                }

                return [$otherParticipant->user_id => $conversation->id];
            });

        return User::query()
            ->whereKeyNot($user->id)
            ->orderBy('name')
            ->get()
            ->values()
            ->map(function (User $contact) use ($existingConversationIds): array {
                return [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'avatarInitials' => $this->avatarInitials($contact->name),
                    'avatarClass' => $this->avatarClassForUser($contact->id),
                    'existingConversationId' => $existingConversationIds[$contact->id] ?? null,
                ];
            })
            ->all();
    }

    /**
     * Load a conversation with full details for the active view.
     */
    private function loadConversationDetails(User $user, int $conversationId): Conversation
    {
        return Conversation::query()
            ->whereHas('participantRecords', function (Builder $query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->whereNull('left_at');
            })
            ->whereKey($conversationId)
            ->with([
                'creator',
                'lastMessage.sender',
                'messages' => function ($query): void {
                    $query->orderBy('created_at')
                        ->orderBy('id')
                        ->with('sender');
                },
                'participants',
                'participantRecords.user',
                'participantRecords.lastReadMessage',
            ])
            ->firstOrFail();
    }

    /**
     * Ensure the conversation belongs to the user.
     */
    private function loadVisibleConversation(User $user, Conversation $conversation): Conversation
    {
        return Conversation::query()
            ->whereHas('participantRecords', function (Builder $query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->whereNull('left_at');
            })
            ->whereKey($conversation->id)
            ->firstOrFail();
    }

    /**
     * Mark the conversation as viewed by the current user.
     */
    private function markConversationAsViewed(Conversation $conversation, User $user): void
    {
        $participant = ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $participant->forceFill([
            'last_read_message_id' => $conversation->messages()->max('id'),
            'last_read_at' => now(),
        ])->save();

        ConversationViewed::dispatch($conversation, $participant);
    }

    /**
     * Convert a conversation to the payload expected by the frontend.
     *
     * @return array<string, mixed>
     */
    private function conversationSummary(Conversation $conversation, User $user): array
    {
        $participant = $conversation->participantRecords
            ->firstWhere('user_id', $user->id);
        $lastMessage = $conversation->lastMessage;
        $displayName = $this->conversationDisplayName($conversation, $user);

        return [
            'id' => $conversation->id,
            'name' => $displayName,
            'email' => $this->conversationSubtitle($conversation, $user),
            'avatarInitials' => $this->avatarInitials($displayName),
            'avatarClass' => $this->avatarClassForConversation($conversation->id),
            'lastMessage' => $lastMessage?->body ?? 'Sin mensajes todavía',
            'lastMessageAt' => $this->formatConversationTimestamp($conversation->last_message_at ?? $conversation->created_at),
            'unreadCount' => $this->unreadCountFor($conversation, $user, $participant),
            'isOnline' => false,
            'statusLabel' => $this->conversationStatusLabel($conversation),
        ];
    }

    /**
     * Convert the active conversation messages to the payload expected by the frontend.
     *
     * @return array<int, array<string, mixed>>
     */
    private function messageSummaries(Conversation $conversation, User $user): array
    {
        $currentParticipant = $conversation->participantRecords
            ->firstWhere('user_id', $user->id);

        return $conversation->messages
            ->map(function (Message $message) use ($conversation, $user, $currentParticipant): array {
                return [
                    'id' => $message->id,
                    'author' => $message->sender_id === $user->id
                        ? 'Tú'
                        : $message->sender?->name ?? 'Usuario',
                    'body' => $message->body,
                    'sentAt' => $this->formatMessageTimestamp($message->created_at),
                    'isMine' => $message->sender_id === $user->id,
                    'status' => $this->messageStatus($conversation, $message, $user, $currentParticipant),
                ];
            })
            ->all();
    }

    /**
     * Get the display name for a conversation.
     */
    private function conversationDisplayName(Conversation $conversation, User $user): string
    {
        if ($conversation->type === 'group') {
            return $conversation->title ?? 'Grupo';
        }

        $otherParticipant = $conversation->participants
            ->first(fn (User $participant): bool => ! $participant->is($user));

        return $otherParticipant?->name
            ?? $conversation->title
            ?? 'Conversación privada';
    }

    /**
     * Get the subtitle for a conversation.
     */
    private function conversationSubtitle(Conversation $conversation, User $user): string
    {
        if ($conversation->type === 'group') {
            return sprintf('%d participantes', $conversation->participants->count());
        }

        return $conversation->participants
            ->first(fn (User $participant): bool => ! $participant->is($user))
            ?->email
            ?? 'Sin correo';
    }

    /**
     * Count unread messages for the current user.
     */
    private function unreadCountFor(Conversation $conversation, User $user, ?ConversationParticipant $participant): int
    {
        if ($participant === null) {
            return 0;
        }

        $query = $conversation->messages()
            ->where('sender_id', '!=', $user->id);

        if ($participant->last_read_message_id !== null) {
            $query->where('id', '>', $participant->last_read_message_id);
        }

        return $query->count();
    }

    /**
     * Determine the status label shown in the sidebar.
     */
    private function conversationStatusLabel(Conversation $conversation): string
    {
        if ($conversation->last_message_at === null) {
            return 'Sin mensajes';
        }

        return $conversation->last_message_at->isToday()
            ? 'Actualizado hoy'
            : ($conversation->last_message_at->isYesterday() ? 'Actualizado ayer' : 'Actualizado recientemente');
    }

    /**
     * Determine a message status for the current user.
     */
    private function messageStatus(
        Conversation $conversation,
        Message $message,
        User $user,
        ?ConversationParticipant $currentParticipant,
    ): string {
        if ($message->sender_id !== $user->id) {
            if ($currentParticipant === null || $currentParticipant->last_read_message_id === null) {
                return 'delivered';
            }

            return $message->id <= $currentParticipant->last_read_message_id ? 'read' : 'delivered';
        }

        $otherParticipants = $conversation->participantRecords
            ->filter(fn (ConversationParticipant $participant): bool => $participant->user_id !== $user->id && $participant->left_at === null);

        return $otherParticipants->every(
            fn (ConversationParticipant $participant): bool => $participant->last_read_message_id !== null
                && $participant->last_read_message_id >= $message->id
        ) ? 'read' : 'delivered';
    }

    /**
     * Format a conversation timestamp for the sidebar.
     */
    private function formatConversationTimestamp(?CarbonInterface $timestamp): string
    {
        if ($timestamp === null) {
            return '';
        }

        if ($timestamp->isToday()) {
            return $timestamp->format('H:i');
        }

        if ($timestamp->isYesterday()) {
            return 'Ayer';
        }

        return $timestamp->format('d/m');
    }

    /**
     * Format a message timestamp.
     */
    private function formatMessageTimestamp(CarbonInterface $timestamp): string
    {
        if ($timestamp->isToday()) {
            return $timestamp->format('H:i');
        }

        if ($timestamp->isYesterday()) {
            return 'Ayer';
        }

        return $timestamp->format('d/m');
    }

    /**
     * Build a stable avatar class from the conversation id.
     */
    private function avatarClassForConversation(int $conversationId): string
    {
        $palette = [
            'bg-emerald-500/15 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200',
            'bg-sky-500/15 text-sky-700 dark:bg-sky-500/20 dark:text-sky-200',
            'bg-violet-500/15 text-violet-700 dark:bg-violet-500/20 dark:text-violet-200',
            'bg-amber-500/15 text-amber-700 dark:bg-amber-500/20 dark:text-amber-200',
            'bg-rose-500/15 text-rose-700 dark:bg-rose-500/20 dark:text-rose-200',
        ];

        return $palette[($conversationId - 1) % count($palette)];
    }

    /**
     * Build a stable avatar class from the user id.
     */
    private function avatarClassForUser(int $userId): string
    {
        $palette = [
            'bg-emerald-500/15 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200',
            'bg-sky-500/15 text-sky-700 dark:bg-sky-500/20 dark:text-sky-200',
            'bg-violet-500/15 text-violet-700 dark:bg-violet-500/20 dark:text-violet-200',
            'bg-amber-500/15 text-amber-700 dark:bg-amber-500/20 dark:text-amber-200',
            'bg-rose-500/15 text-rose-700 dark:bg-rose-500/20 dark:text-rose-200',
        ];

        return $palette[($userId - 1) % count($palette)];
    }

    /**
     * Build the canonical direct conversation key for two users.
     */
    private function directConversationKey(int $firstUserId, int $secondUserId): string
    {
        return collect([$firstUserId, $secondUserId])
            ->sort()
            ->join(':');
    }

    /**
     * Build initials from a display name.
     */
    private function avatarInitials(string $displayName): string
    {
        $parts = array_values(array_filter(explode(' ', $displayName)));

        return collect($parts)
            ->take(2)
            ->map(fn (string $part): string => mb_substr($part, 0, 1))
            ->implode('');
    }
}
