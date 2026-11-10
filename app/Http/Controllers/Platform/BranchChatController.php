<?php

namespace App\Http\Controllers\Platform;

use App\Enums\ConversationType;
use App\Http\Controllers\Concerns\InteractsWithConversations;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConversationMessageRequest;
use App\Http\Requests\StorePlatformBranchChatRequest;
use App\Models\Branch;
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

class BranchChatController extends Controller
{
    use InteractsWithConversations;

    public function __construct(private ConversationService $conversations) {}

    public function index(Request $request): Response
    {
        return Inertia::render(
            $this->conversationPageComponent(),
            $this->workspacePayload($request, $this->viewer($request)),
        );
    }

    public function branchOptions(Request $request): JsonResponse
    {
        $term = trim((string) $request->string('search'));

        $branches = Branch::query()
            ->when($term !== '', fn ($query) => $query->where('name', 'like', '%'.$term.'%'))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'slug']);

        return response()->json([
            'options' => $branches->map(fn (Branch $branch): array => [
                'value' => $branch->id,
                'label' => $branch->name,
                'description' => $branch->slug,
            ])->all(),
        ]);
    }

    public function store(StorePlatformBranchChatRequest $request): RedirectResponse
    {
        $viewer = $this->viewer($request);
        Gate::forUser($viewer)->authorize('createPlatformBranch', Conversation::class);

        $branch = $request->branch();
        $conversation = $this->conversations->openPlatformBranch($branch, $viewer);

        Toast::success('Chat opened with '.$branch->name.'.');

        return redirect()->route('platform.branch-chats.show', $conversation);
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
            $this->workspacePayload($request, $viewer, $conversation, $lastRead),
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

    public function close(Request $request, Conversation $conversation): RedirectResponse
    {
        $viewer = $this->viewer($request);
        $this->authorizeConversation($conversation, $viewer, 'close');

        $this->conversations->close(
            $conversation,
            'Conversation closed by '.$viewer->name.'.',
        );

        Toast::success('Conversation closed.');

        return redirect()->route('platform.branch-chats.show', $conversation);
    }

    protected function conversationType(): ConversationType
    {
        return ConversationType::PlatformBranch;
    }

    protected function conversationBranchId(): ?int
    {
        return null;
    }

    protected function conversationPageComponent(): string
    {
        return 'platform/branch-chats/index';
    }

    protected function conversationIndexUrl(): string
    {
        return route('platform.branch-chats.index');
    }

    /**
     * @return array<string, mixed>
     */
    protected function conversationEndpoints(Conversation $conversation): array
    {
        return [
            'self' => route('platform.branch-chats.show', $conversation),
            'messages' => route('platform.branch-chats.messages', $conversation),
            'send' => route('platform.branch-chats.messages.store', $conversation),
            'delivered' => route('platform.branch-chats.delivered', $conversation),
            'read' => route('platform.branch-chats.read', $conversation),
            'close' => route('platform.branch-chats.close', $conversation),
        ];
    }

    private function viewer(Request $request): User
    {
        /** @var User $user */
        $user = $request->user('platform');

        return $user;
    }
}
