<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory()->published(),
            'amount' => fn (array $attributes) => Course::findOrFail((int) $attributes['course_id'])->price,
            'currency' => 'USD',
            'method' => PaymentMethod::BankTransfer,
            'status' => PaymentStatus::Pending,
            'confirmed_by' => null,
            'confirmed_at' => null,
            'notes' => null,
        ];
    }

    /**
     * Indicate that the payment has been confirmed by an admin.
     */
    public function confirmed(?User $by = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Confirmed,
            'confirmed_by' => $by === null ? User::factory()->admin() : $by->id,
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Indicate that the payment has been rejected by an admin.
     */
    public function rejected(?User $by = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Rejected,
            'confirmed_by' => $by === null ? User::factory()->admin() : $by->id,
            'confirmed_at' => now(),
            'notes' => fake()->sentence(),
        ]);
    }

    /**
     * Indicate that the payment was made in cash.
     */
    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => PaymentMethod::Cash,
        ]);
    }
}
