<?php

namespace App\Http\Requests\Customer;

use App\Enums\UserType;
use App\Services\Customer\ProductToReviewService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StoreProductReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->user_type === UserType::CUSTOMER;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $user = $this->user();

                if ($user === null) {
                    throw new NotFoundHttpException;
                }

                /** @var ProductToReviewService $reviews */
                $reviews = $this->container->make(ProductToReviewService::class);
                $item = $reviews->findItemForCustomer($user, (int) $this->route('item'));

                if ($item === null) {
                    throw new NotFoundHttpException;
                }

                if ($reviews->hasReview($item)) {
                    $validator->errors()->add(
                        'body',
                        'You have already reviewed this product for this order.',
                    );
                }
            },
        ];
    }
}
