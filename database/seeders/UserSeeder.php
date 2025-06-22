<?php

namespace Database\Seeders;

use App\Models\Administrador;
use App\Models\Guardia;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Usuario para Administrador
        $userAdmin = User::create([
            'name' => 'Administrador',
            'email' => 'admin@example.com',
            'password' => Hash::make('12345678'),
            'monthly_fee' => 50.00,
            'payment_status' => true,
            'last_payment_date' => now(),
            'next_payment_due' => now()->addMonth(),
        ]);

        $personaAdmin = Persona::create([
            'nombre' => 'Christian',
            'apellido_p' => 'Carrion',
            'apellido_m' => 'Ojeda',
            'ci' => '11111111',
            'telefono' => '62066339',
            'foto' => '',
            'user_id' => $userAdmin->id,
        ]);

        $admin1 = Administrador::create([
            'persona_id' => $personaAdmin->id
        ]);

        // Usuario para Guardia
        $userGuardia = User::create([
            'name' => 'Guardia',
            'email' => 'guardia@example.com',
            'password' => Hash::make('12345678'),
            'monthly_fee' => 50.00,
            'payment_status' => false, // Sin pago para probar el bloqueo
            'last_payment_date' => now()->subMonth(),
            'next_payment_due' => now()->subDays(5), // Vencido hace 5 días
        ]);

        $personaGuardia = Persona::create([
            'nombre' => 'Juan',
            'apellido_p' => 'Pérez',
            'apellido_m' => 'García',
            'ci' => '22222222',
            'telefono' => '70123456',
            'foto' => '',
            'user_id' => $userGuardia->id,
        ]);

        $guardia1 = Guardia::create([
            'estado' => '1',
            'fecha_ini' => '2025/05/05',
            'persona_id' => $personaGuardia->id,
            'administrador_id' => $admin1->id, // Vincular con el administrador creado
        ]);

        // Usuario adicional para el guard 'web' (opcional)
        $userWeb = User::create([
            'name' => 'Usuario Web',
            'email' => 'user@example.com',
            'password' => Hash::make('12345678'),
            'monthly_fee' => 50.00,
            'payment_status' => true,
            'last_payment_date' => now(),
            'next_payment_due' => now()->addMonth(),
        ]);

        $personaWeb = Persona::create([
            'nombre' => 'María',
            'apellido_p' => 'López',
            'apellido_m' => 'Martínez',
            'ci' => '33333333',
            'telefono' => '75987654',
            'foto' => '',
            'user_id' => $userWeb->id,
        ]);
    }
}
