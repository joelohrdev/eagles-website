<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Order $order */
                $order = $this->route('order');
                $status = $this->input('status');

                if ($status === null || $status === $order->status->value) {
                    return;
                }

                $allowed = match ($order->status) {
                    OrderStatus::Paid => [OrderStatus::Fulfilled, OrderStatus::Cancelled, OrderStatus::Refunded],
                    OrderStatus::Fulfilled => [OrderStatus::Refunded, OrderStatus::Paid],
                    OrderStatus::Pending => [OrderStatus::Cancelled],
                    default => [],
                };

                if (! in_array(OrderStatus::from($status), $allowed, true)) {
                    $validator->errors()->add('status', __('That status change is not allowed from :from.', ['from' => $order->status->label()]));
                }
            },
        ];
    }
}
