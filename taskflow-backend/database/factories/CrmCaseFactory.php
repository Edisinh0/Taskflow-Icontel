<?php

namespace Database\Factories;

use App\Models\CrmCase;
use App\Models\User;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class CrmCaseFactory extends Factory
{
    protected $model = CrmCase::class;

    public function definition(): array
    {
        return [
            'case_number' => 'CASE-' . $this->faker->unique()->numerify('###'),
            'subject' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['Open', 'In Progress', 'Closed']),
            'priority' => $this->faker->randomElement(['Low', 'Medium', 'High', 'Critical']),
            'type' => $this->faker->randomElement(['Issue', 'Request', 'Bug', 'Feature']),
            'area' => $this->faker->randomElement(['SAC', 'Operations', 'Sales']),
            'client_id' => Client::factory(),
            'sweetcrm_id' => $this->faker->unique()->word(),
            'sweetcrm_assigned_user_id' => null,
            'created_by' => User::factory(),
            'closure_status' => 'open',
            'closure_requested_by_id' => null,
            'closure_requested_at' => null,
            'closure_approved_by_id' => null,
            'closure_approved_at' => null,
            'sweetcrm_synced_at' => $this->faker->dateTime(),
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Open',
            'closure_status' => 'open',
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Closed',
            'closure_status' => 'closed',
        ]);
    }

    public function closureRequested(): static
    {
        return $this->state(fn (array $attributes) => [
            'closure_status' => 'closure_requested',
            'closure_requested_by_id' => User::factory(),
            'closure_requested_at' => now(),
        ]);
    }
}
