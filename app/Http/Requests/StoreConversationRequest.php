<?php

namespace App\Http\Requests;

use App\Models\Branch;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Opens a thread for an existing order.
 *
 * The branch scope comes from the bound `{branch}` route parameter, never from
 * the client, so a branch operator cannot open a thread on another branch's
 * order by posting a foreign id.
 */
class StoreConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $branchId = $this->scopedBranchId();

        return [
            'order_id' => [
                'required',
                'integer',
                Rule::exists('orders', 'id')->when(
                    $branchId !== null,
                    fn ($rule) => $rule->where(fn ($query) => $query->whereExists(
                        fn ($sub) => $sub->selectRaw('1')
                            ->from('order_fulfillments')
                            ->whereColumn('order_fulfillments.order_id', 'orders.id')
                            ->where('order_fulfillments.branch_id', $branchId),
                    )),
                ),
                // `conversations.order_id` is unique; surfacing it here lets
                // Precognition warn in the picker before the operator submits.
                Rule::unique('conversations', 'order_id'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'order_id.exists' => 'That order is not available for this branch.',
            'order_id.unique' => 'That order already has a conversation.',
        ];
    }

    public function order(): Order
    {
        return Order::query()->findOrFail($this->integer('order_id'));
    }

    private function scopedBranchId(): ?int
    {
        $branch = $this->route('branch');

        return $branch instanceof Branch ? $branch->id : null;
    }
}
