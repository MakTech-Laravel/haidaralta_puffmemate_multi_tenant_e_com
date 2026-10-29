<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\ConversationType;
use App\Events\ConversationInboxUpdated;
use App\Models\Branch;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use App\Support\ConversationInbox;
use App\Support\ConversationPayload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Thread behaviour shared by the platform and branch conversation controllers.
 *
 * The only difference between the two portals is the branch scope, so each
 * controller supplies that and everything else is identical.
 */
trait InteractsWithConversations
{
    /**
     * Newest slice of the transcript rendered on first paint.
     */
    public const THREAD_PAGE_SIZE = 40;

    /**
     * Branch id this portal is locked to, or null for platform-wide access.
     */
    abstract protected function conversationBranchId(): ?int;

    /**
     * Inertia page shared by the inbox and the thread.
     *
     * Both routes render the same component so moving between threads swaps the
     * right-hand pane without remounting the conversation list.
     */
    abstract protected function conversationPageComponent(): string;

    /**
     * Routes the client calls for a given thread, resolved per portal.
     *
     * @return array<string, mixed>
     */
    abstract protected function conversationEndpoints(Conversation $conversation): array;

    abstract protected function conversationIndexUrl(): string;

    protected function conversationType(): ConversationType
    {
        return ConversationType::Order;
    }

    /**
     * Whether message DTOs include sender role labels (branch/platform yes; driver no).
     */
    protected function includeSenderRoleInPayload(): bool
    {
        return true;
    }

    /**
     * The whole workspace: inbox on the left, optional thread on the right.
     *
     * `conversations` is a merge prop so the list appends as it pages instead
     * of being replaced, and both list props reload independently of `thread`
     * so searching never refetches the open transcript.
     *
     * @return array<string, mixed>
     */
    protected function workspacePayload(
        Request $request,
        User $viewer,
        ?Conversation $conversation = null,
    ): array {
        $inbox = null;

        $resolve = function () use (&$inbox, $request, $viewer): array {
            return $inbox ??= (new ConversationInbox(
                $request,
                $viewer,
                $this->conversationBranchId(),
                $this->conversationType(),
            ))->toArray();
        };

        return [
            'conversations' => Inertia::merge(fn (): array => $resolve()['items'])->matchOn('id'),
            'inboxMeta' => fn (): array => Arr::except($resolve(), 'items'),
            'thread' => $conversation === null
                ? null
                : $this->threadPayload($conversation, $viewer),
            'viewerId' => $viewer->id,
            'viewerName' => $viewer->name,
            'indexUrl' => $this->conversationIndexUrl(),
            'inboxChannel' => ConversationInboxUpdated::channelFor($viewer->id),
        ];
    }

    /**
     * Cross-branch access is a 404, not a 403: the thread does not exist here.
     */
    protected function authorizeConversation(Conversation $conversation, User $viewer, string $ability = 'view'): void
    {
        $branchId = $this->conversationBranchId();

        abort_if($branchId !== null && $conversation->branch_id !== $branchId, 404);
        abort_if($conversation->type !== $this->conversationType(), 404);

        Gate::forUser($viewer)->authorize($ability, $conversation);
    }

    /**
     * @return array<string, mixed>
     */
    protected function threadPayload(Conversation $conversation, User $viewer): array
    {
        $conversation->load([
            'order:id,order_number,status',
            'branch:id,name,slug',
            'driver:id,name',
            'driver.media',
            'participants.user:id,name',
            'participants.user.media',
            'participants.user.roles',
        ]);

        $messages = $conversation->messages()
            ->with(['sender:id,name', 'sender.media', 'attachments'])
            ->orderByDesc('id')
            ->limit(self::THREAD_PAGE_SIZE + 1)
            ->get();

        $hasMore = $messages->count() > self::THREAD_PAGE_SIZE;
        $messages = $messages->take(self::THREAD_PAGE_SIZE)->reverse()->values();

        return [
            'conversation' => ConversationPayload::conversation($conversation),
            'messages' => ConversationPayload::messages($messages, $this->includeSenderRoleInPayload()),
            'hasMore' => $hasMore,
            'canSend' => Gate::forUser($viewer)->allows('send', $conversation),
            'canClose' => Gate::forUser($viewer)->allows('close', $conversation),
            'endpoints' => $this->conversationEndpoints($conversation),
        ];
    }

    /**
     * Older history for the load-earlier row, keyed on message id.
     */
    protected function olderMessages(Request $request, Conversation $conversation): JsonResponse
    {
        $before = (int) $request->integer('before');

        $messages = $conversation->messages()
            ->with(['sender:id,name', 'sender.media', 'attachments'])
            ->when($before > 0, fn (Builder $query) => $query->where('id', '<', $before))
            ->orderByDesc('id')
            ->limit(self::THREAD_PAGE_SIZE + 1)
            ->get();

        $hasMore = $messages->count() > self::THREAD_PAGE_SIZE;
        $messages = $messages->take(self::THREAD_PAGE_SIZE)->reverse()->values();

        return response()->json([
            'messages' => ConversationPayload::messages($messages, $this->includeSenderRoleInPayload()),
            'hasMore' => $hasMore,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function sentMessageResponse(Message $message): array
    {
        $message->load(['sender:id,name', 'sender.media', 'attachments']);

        return ['message' => ConversationPayload::message($message, $this->includeSenderRoleInPayload())];
    }

    /**
     * Branches that have at least one thread, paginated for the inbox filter.
     *
     * Same `{data, meta}` contract as the stock and category option endpoints,
     * so the shared `InfiniteScrollCombobox` consumes it without adaptation.
     *
     * @return array{
     *     data: list<array{id: int, name: string, slug: string}>,
     *     meta: array{current_page: int, last_page: int, per_page: int, total: int}
     * }
     */
    protected function conversationBranchOptions(Request $request): array
    {
        $term = trim((string) $request->string('filter.search', $request->string('search')));
        $perPage = max(10, min(50, (int) $request->integer('per_page', 20)));

        $paginator = Branch::query()
            ->whereHas('conversations')
            ->when($term !== '', fn (Builder $query) => $query->where(
                fn (Builder $inner) => $inner
                    ->where('name', 'like', '%'.$term.'%')
                    ->orWhere('slug', 'like', '%'.$term.'%'),
            ))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage);

        $data = [];

        foreach ($paginator->items() as $branch) {
            $data[] = [
                'id' => (int) $branch->id,
                'name' => $branch->name,
                'slug' => $branch->slug,
            ];
        }

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /**
     * Orders that can still have a thread opened against them.
     *
     * @return list<array{value: int, label: string, description: string}>
     */
    protected function openableOrderOptions(Request $request): array
    {
        $branchId = $this->conversationBranchId();
        $term = trim((string) $request->string('search'));

        $orders = Order::query()
            ->with('primaryFulfillment.branch:id,name')
            ->whereDoesntHave('conversation')
            ->when(
                $branchId !== null,
                fn (Builder $query) => $query->whereHas('fulfillments', fn (Builder $inner) => $inner->where('branch_id', $branchId)),
            )
            ->when($term !== '', fn (Builder $query) => $query->where('order_number', 'like', '%'.$term.'%'))
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $options = [];

        foreach ($orders as $order) {
            $branchName = $order->primaryFulfillment?->branch?->name ?? 'Unassigned';

            $options[] = [
                'value' => $order->id,
                'label' => $order->order_number,
                'description' => $branchName.' · '.$order->status->label(),
            ];
        }

        return $options;
    }
}
