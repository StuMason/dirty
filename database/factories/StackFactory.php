<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Stack>
 */
class StackFactory extends Factory
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
            'name' => $this->faker->name,
            'env' => $this->faker->word,
            'env_key' => $this->faker->word,
            'bucket' => $this->faker->word,
            'region' => $this->faker->word,
            'account' => $this->faker->word,
            'function_name_artisan' => $this->faker->word,
            'function_name_web' => $this->faker->word,
            'function_name_worker' => $this->faker->word,
            'distribution_url' => $this->faker->word,
            'queue_name' => $this->faker->word,
        ];
    }
}
