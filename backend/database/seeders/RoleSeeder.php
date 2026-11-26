<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Administrator', 'slug' => 'admin'],
            ['name' => 'Employer', 'slug' => 'employer'],
            ['name' => 'Candidate', 'slug' => 'candidate'],
        ] as $role) {
            Role::query()->updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
