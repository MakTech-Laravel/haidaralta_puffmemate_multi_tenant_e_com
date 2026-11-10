<?php

namespace App\Http\Controllers\Driver;

use App\Enums\ConversationType;
use App\Enums\DriverAssignmentStatus;
use App\Http\Controllers\Concerns\InteractsWithConversations;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConversationMessageRequest;
use App\Http\Requests\StoreDriverChatRequest;
use App\Models\Conversation;
use App\Models\User;
use App\Services\Conversation\ConversationService;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DriverChatController extends Controller
{
    use InteractsWithConversations;

    public function __construct(private ConversationService $conversations) {}

    public function index(Request $request): Response|RedirectResponse
    {
        $viewer = $this->viewer($request);

        $existing = Conversation::query()
            ->where('type', ConversationType::DriverOps)
            ->where('driver_id', $viewer->id)
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->first();

        if ($existing !== null) {
            return redirect()->route('driver.chats.show', $existing);
        }

        return Inertia::render(
            $this->conversationPageComponent(),
            [
                ...$this->workspacePayload($request, $viewer),
                'storeUrl' => route('driver.chats.store'),
            ],
        );
    }

    public function branchOptions(Request $request): JsonResponse
    {
        return response()->json([
            'options' => $this->activeBranches($this->viewer($request)),
        ]);
    }

    public function store(StoreDriverChatRequest $request): RedirectResponse
    {
        $viewer = $this->viewer($request);
        Gate::forUser($viewer)->authorize('createDriverOps', Conversation::class);

        $branch = $request->branch();
        $conversation = $this->conversations->openDriverOps($branch, $viewer, $viewer);

        Toast::success('Chat opened with '.$branch->name.'.');

        return redirect()->route('driver.chats.show', $conversation);
    }

    public function show(Request $request, Conversation $conversation): Response
    {
        $viewer = $this->viewer($request);
        $this->authorizeConversation($conversation, $viewer);
        $this->conversations->joinOperator($conversation, $viewer);
        $lastRead = $this->viewerLastReadMessageId($conversation, $viewer);
        $this->conversations->markRead($conversation, $viewer);

        return Inertia::render(
            $this->conversationPageComponent(),
            [
                ...$this->workspacePayload($request, $viewer, $conversation, $lastRead),
                'storeUrl' => route('driver.chats.store'),
            ],
        );
    }

    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeConversation($conversation, $this->viewer($request));

        return $this->olderMessages($request, $conversation);
    }

    public function storeMessage(
        StoreConversationMessageRequest $request,
        Conversation $conversation,
    ): JsonResponse {
        $viewer = $this->viewer($request);
        $this->authorizeConversation($conversation, $viewer, 'send');

        $message = $this->conversations->sendMessage(
            $conversation,
            $viewer,
            $request->body(),
            $request->attachments(),
        );

        return response()->json($this->sentMessageResponse($message), 201);
    }

    public function delivered(Request $request, Conversation $conversation): JsonResponse
    {
        $viewer = $this->viewer($request);
        $this->authorizeConversation($conversation, $viewer);

        $this->conversations->markDelivered($conversation, $viewer, $request->integer('message_id') ?: null);

        return response()->json(['status' => 'ok']);
    }

    public function read(Request $request, Conversation $conversation): JsonResponse
    {
        $viewer = $this->viewer($request);
        $this->authorizeConversation($conversation, $viewer);

        $this->conversations->markRead($conversation, $viewer, $request->integer('message_id') ?: null);

        return response()->json(['status' => 'ok']);
    }

    protected function conversationType(): ConversationType
    {
        return ConversationType::DriverOps;
    }

    protected function includeSenderRoleInPayload(): bool
    {
        return false;
    }

    protected function conversationBranchId(): ?int
    {
        return null;
    }

    protected function conversationPageComponent(): string
    {
        return 'driver/chats/index';
    }

    protected function conversationIndexUrl(): string
    {
        return route('driver.chats.index');
    }

    /**
     * @return array<string, mixed>
     */
    protected function conversationEndpoints(Conversation $conversation): array
    {
        return [
            'self' => route('driver.chats.show', $conversation),
            'messages' => route('driver.chats.messages', $conversation),
            'send' => route('driver.chats.messages.store', $conversation),
            'delivered' => route('driver.chats.delivered', $conversation),
            'read' => route('driver.chats.read', $conversation),
            'close' => '',
        ];
    }

    private function viewer(Request $request): User
    {
        /** @var User $user */
        $user = $request->user('driver');

        return $user;
    }

    /**
     * @return list<array{value: int, label: string, description: string}>
     */
    private function activeBranches(User $driver): array
    {
        return $driver->driverBranchAssignments()
            ->where('status', DriverAssignmentStatus::Active)
            ->whereNull('ended_at')
            ->with('branch:id,name')
            ->get()
            ->map(fn ($assignment): array => [
                'value' => (int) $assignment->branch_id,
                'label' => $assignment->branch?->name ?? 'Branch',
                'description' => 'Branch',
            ])
            ->values()
            ->all();
    }
}
