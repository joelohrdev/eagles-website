<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserRoleRequest extends FormRequest
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
            'role' => ['required', Rule::enum(UserRole::class)],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var User $target */
                $target = $this->route('user');

                if ($target->is($this->user())) {
                    $validator->errors()->add('role', __('You cannot change your own role.'));

                    return;
                }

                $demotingAdmin = $target->isAdmin() && $this->input('role') !== UserRole::Admin->value;

                if ($demotingAdmin && User::query()->where('role', UserRole::Admin)->count() <= 1) {
                    $validator->errors()->add('role', __('There must be at least one admin.'));
                }
            },
        ];
    }
}
