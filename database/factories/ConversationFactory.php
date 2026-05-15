<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'direct',
            'title' => null,
            'created_by_user_id' => User::factory(),
            'direct_key' => fake()->unique()->uuid(),
            'last_message_id' => null,
            'last_message_at' => null,
        ];
    }

    public function group(): static
    {
        return $this->state(fn (): array => [
            'type' => 'group',
            'title' => fake()->sentence(3),
            'direct_key' => null,
        ]);
    }
}
