<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    private static int $sequence = 1000;

    public function definition(): array
    {
        $number = self::$sequence++;

        return [
            'employee_number' => sprintf('PHM-TEST-%04d', $number),
            'full_name' => fake('id_ID')->name(),
            'email' => null,
            'phone' => null,
            'password' => 'password',
            'department_id' => Department::query()->inRandomOrder()->value('id'),
            'work_shift_id' => WorkShift::query()->inRandomOrder()->value('id'),
            'position' => 'Karyawan',
            'work_location' => 'Plant 1',
            'status' => 'active',
            'is_demo' => false,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }

    public function demo(): static
    {
        return $this->state(fn () => ['is_demo' => true]);
    }
}
