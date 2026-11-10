<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Concerns\InteractsWithConversations;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConversationMessageRequest;
use App\Http\Requests\StoreConversationRequest;
use App\Models\Branch;
use App\Models\Conversation;
use App\Models\User;
use App\Services\Conversation\ConversationService;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConversationController extends Controller
{
    use InteractsWithConversations;

    private ?Branch $branch = null;

    public function __construct(private ConversationService $conversations) {}

    /**
     * Branch operators only ever see their own branch's order chats.
     */
    public function index(Request $request, Branch $branch): Response
    {
        $this->branch = $branch;

        return Inertia::render(
            $this->conversationPageComponent(),
            $this->workspacePayload($request, $this->viewer($request)),
        );
    }

    public function orderOptions(Request $request, Branch $branch): JsonResponse
    {
        $this->branch = $branch;

        return response()->json(['options' => $this->openableOrderOptions($request)]);
    }

    public function store(StoreConversationRequest $request, Branch $branch): RedirectResponse
    {
        $this->branch = $branch;

        $order = $request->order();

        $conversation = $this->conversations->openForOrder($order);
        $this->conversations->joinOperator($conversation, $this->viewer($request));

        Toast::success('Conversation opened for order '.$order->order_number.'.');

        return redirect()->route('branch.conversations.show', [$branch, $conversation]);
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

    /**
     * Client acknowledgement that a message reached this operator.
     */
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

        return redirect()->route('branch.conversations.show', [$branch, $conversation]);
    }

    protected function conversationBranchId(): ?int
    {
        return $this->branch?->id;
    }

    protected function conversationPageComponent(): string
    {
        return 'branch/conversations/index';
    }

    protected function conversationIndexUrl(): string
    {
        return route('branch.conversations.index', $this->branch);
    }

    /**
     * @return array<string, mixed>
     */
    protected function conversationEndpoints(Conversation $conversation): array
    {
        $branch = $this->branch;

        return [
            'self' => route('branch.conversations.show', [$branch, $conversation]),
            'messages' => route('branch.conversations.messages', [$branch, $conversation]),
            'send' => route('branch.conversations.messages.store', [$branch, $conversation]),
            'delivered' => route('branch.conversations.delivered', [$branch, $conversation]),
            'read' => route('branch.conversations.read', [$branch, $conversation]),
            'close' => route('branch.conversations.close', [$branch, $conversation]),
        ];
    }

    private function viewer(Request $request): User
    {
        /** @var User $user */
        $user = $request->user('branch');

        return $user;
    }
}
