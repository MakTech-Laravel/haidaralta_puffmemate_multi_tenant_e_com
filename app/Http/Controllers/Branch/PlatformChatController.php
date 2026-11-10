<?php

namespace App\Http\Controllers\Branch;

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

class PlatformChatController extends Controller
{
    use InteractsWithConversations;

    private ?Branch $branch = null;

    public function __construct(private ConversationService $conversations) {}

    public function index(Request $request, Branch $branch): Response|RedirectResponse
    {
        $this->branch = $branch;

        $existing = Conversation::query()
            ->where('type', ConversationType::PlatformBranch)
            ->where('branch_id', $branch->id)
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->first();

        if ($existing !== null) {
            return redirect()->route('branch.platform-chats.show', [$branch, $existing]);
        }

        return Inertia::render(
            $this->conversationPageComponent(),
            $this->workspacePayload($request, $this->viewer($request)),
        );
    }

    public function platformOptions(Request $request, Branch $branch): JsonResponse
    {
        $this->branch = $branch;

        return response()->json([
            'options' => [
                [
                    'value' => $branch->id,
                    'label' => 'Platform',
                    'description' => 'Headquarters',
                ],
            ],
        ]);
    }

    public function store(StorePlatformBranchChatRequest $request, Branch $branch): RedirectResponse
    {
        $this->branch = $branch;
        $viewer = $this->viewer($request);
        Gate::forUser($viewer)->authorize('createPlatformBranch', Conversation::class);

        $conversation = $this->conversations->openPlatformBranch($branch, $viewer);

        Toast::success('Chat opened with platform.');

        return redirect()->route('branch.platform-chats.show', [$branch, $conversation]);
    }

    public function show(Request $request, Branch $branch, Conversation $conversation): Response
    {
        $this->branch = $branch;
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

    public function messages(Request $request, Branch $branch, Conversation $conversation): JsonResponse
    {
        $this->branch = $branch;
        $this->authorizeConversation($conversation, $this->viewer($request));

        return $this->olderMessages($request, $conversation);
    }

    public function storeMessage(
        StoreConversationMessageRequest $request,
        Branch $branch,
        Conversation $conversation,
    ): JsonResponse {
        $this->branch = $branch;
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

    public function delivered(Request $request, Branch $branch, Conversation $conversation): JsonResponse
    {
        $this->branch = $branch;
        $viewer = $this->viewer($request);
        $this->authorizeConversation($conversation, $viewer);

        $this->conversations->markDelivered($conversation, $viewer, $request->integer('message_id') ?: null);

        return response()->json(['status' => 'ok']);
    }

    public function read(Request $request, Branch $branch, Conversation $conversation): JsonResponse
    {
        $this->branch = $branch;
        $viewer = $this->viewer($request);
        $this->authorizeConversation($conversation, $viewer);

        $this->conversations->markRead($conversation, $viewer, $request->integer('message_id') ?: null);

        return response()->json(['status' => 'ok']);
    }

    public function close(Request $request, Branch $branch, Conversation $conversation): RedirectResponse
    {
        $this->branch = $branch;
        $viewer = $this->viewer($request);
        $this->authorizeConversation($conversation, $viewer, 'close');

        $this->conversations->close(
            $conversation,
            'Conversation closed by '.$viewer->name.'.',
        );

        Toast::success('Conversation closed.');

        return redirect()->route('branch.platform-chats.show', [$branch, $conversation]);
    }

    protected function conversationType(): ConversationType
    {
        return ConversationType::PlatformBranch;
    }

    protected function conversationBranchId(): ?int
    {
        return $this->branch?->id;
    }

    protected function conversationPageComponent(): string
    {
        return 'branch/platform-chats/index';
    }

    protected function conversationIndexUrl(): string
    {
        return route('branch.platform-chats.index', $this->branch);
    }

    /**
     * @return array<string, mixed>
     */
    protected function conversationEndpoints(Conversation $conversation): array
    {
        $branch = $this->branch;

        return [
            'self' => route('branch.platform-chats.show', [$branch, $conversation]),
            'messages' => route('branch.platform-chats.messages', [$branch, $conversation]),
            'send' => route('branch.platform-chats.messages.store', [$branch, $conversation]),
            'delivered' => route('branch.platform-chats.delivered', [$branch, $conversation]),
            'read' => route('branch.platform-chats.read', [$branch, $conversation]),
            'close' => route('branch.platform-chats.close', [$branch, $conversation]),
        ];
    }

    private function viewer(Request $request): User
    {
        /** @var User $user */
        $user = $request->user('branch');

        return $user;
    }
}
