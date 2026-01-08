<?php

namespace Database\Factories;

use App\Models\CaseClosureRequest;
use App\Models\CrmCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CaseClosureRequestFactory extends Factory
{
    protected $model = CaseClosureRequest::class;

    public function definition(): array
    {
        return [
            'case_id' => CrmCase::factory(),
            'requested_by_user_id' => User::factory(),
            'assigned_to_user_id' => User::factory(),
            'reason' => $this->faker->paragraph(),
            'completion_percentage' => $this->faker->numberBetween(0, 100),
            'status' => 'pending',
            'rejection_reason' => null,
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'reviewed_by_user_id' => User::factory(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'rejection_reason' => $this->faker->sentence(),
            'reviewed_by_user_id' => User::factory(),
            'reviewed_at' => now(),
        ]);
    }
}
