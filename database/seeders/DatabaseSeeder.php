<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Operador
        User::create([
            'name' => 'Carlos Operador',
            'email' => 'operador@email.com',
            'password' => Hash::make('senha123'),
            'profile' => 'OPERADOR',
        ]);

        // Gestor
        User::create([
            'name' => 'Ana Gestora',
            'email' => 'gestor@email.com',
            'password' => Hash::make('senha123'),
            'profile' => 'GESTOR',
        ]);

        // Liderança
        User::create([
            'name' => 'Bruno Liderança',
            'email' => 'lideranca@email.com',
            'password' => Hash::make('senha123'),
            'profile' => 'LIDERANCA',
        ]);
    }
}
