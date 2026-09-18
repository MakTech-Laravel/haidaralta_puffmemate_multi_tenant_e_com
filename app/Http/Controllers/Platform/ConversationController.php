<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Concerns\InteractsWithConversations;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConversationMessageRequest;
use App\Http\Requests\StoreConversationRequest;
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

    public function __construct(private ConversationService $conversations) {}

    /**
     * Platform operators see order chats across every branch.
     */
    public function index(Request $request): Response
    {
        return Inertia::render(
            $this->conversationPageComponent(),
            $this->workspacePayload($request, $this->viewer($request)),
        );
    }

    /**
     * Orders without a thread, for the New conversation picker.
     */
    public function orderOptions(Request $request): JsonResponse
    {
        return response()->json(['options' => $this->openableOrderOptions($request)]);
    }

    /**
     * Open a thread for an order, or jump to the existing one.
     */
    public function store(StoreConversationRequest $request): RedirectResponse
    {
        $order = $request->order();

        $conversation = $this->conversations->openForOrder($order);
        $this->conversations->joinOperator($conversation, $this->viewer($request));

        Toast::success('Conversation opened for order '.$order->order_number.'.');

        return redirect()->route('platform.conversations.show', $conversation);
    }

    public function show(Request $request, Conversation $conversation): Response
    {
        $viewer = $this->viewer($request);
        $this->authorizeConversation($conversation, $viewer);
        $this->conversations->joinOperator($conversation, $viewer);
        $this->conversations->markRead($conversation, $viewer);

        return Inertia::render(
            $this->conversationPageComponent(),
            $this->workspacePayload($request, $viewer, $conversation),
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

    /**
     * Client acknowledgement that a message reached this operator.
     */
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

        return redirect()->route('platform.conversations.show', $conversation);
    }

    /**
     * Platform operators are not branch-scoped.
     */
    protected function conversationBranchId(): ?int
    {
        return null;
    }

    protected function conversationPageComponent(): string
    {
        return 'platform/conversations/index';
    }

    protected function conversationIndexUrl(): string
    {
        return route('platform.conversations.index');
    }

    /**
     * @return array<string, mixed>
     */
    protected function conversationEndpoints(Conversation $conversation): array
    {
        return [
            'self' => route('platform.conversations.show', $conversation),
            'messages' => route('platform.conversations.messages', $conversation),
            'send' => route('platform.conversations.messages.store', $conversation),
            'delivered' => route('platform.conversations.delivered', $conversation),
            'read' => route('platform.conversations.read', $conversation),
            'close' => route('platform.conversations.close', $conversation),
        ];
    }

    private function viewer(Request $request): User
    {
        /** @var User $user */
        $user = $request->user('platform');

        return $user;
    }
}
