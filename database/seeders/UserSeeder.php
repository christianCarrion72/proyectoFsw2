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
            'administrador_id' => $admin1->id,
        ]);

        // Guardia 2 - Sin pago
        $userGuardia2 = User::create([
            'name' => 'Guardia 2',
            'email' => 'guardia2@example.com',
            'password' => Hash::make('12345678'),
            'monthly_fee' => 50.00,
            'payment_status' => false,
            'last_payment_date' => now()->subMonth(),
            'next_payment_due' => now()->subDays(10),
        ]);

        $personaGuardia2 = Persona::create([
            'nombre' => 'Carlos',
            'apellido_p' => 'Rodríguez',
            'apellido_m' => 'Silva',
            'ci' => '44444444',
            'telefono' => '71234567',
            'foto' => '',
            'user_id' => $userGuardia2->id,
        ]);

        $guardia2 = Guardia::create([
            'estado' => '1',
            'fecha_ini' => '2025/05/10',
            'persona_id' => $personaGuardia2->id,
            'administrador_id' => $admin1->id,
        ]);

        // Guardia 3 - Sin pago
        $userGuardia3 = User::create([
            'name' => 'Guardia 3',
            'email' => 'guardia3@example.com',
            'password' => Hash::make('12345678'),
            'monthly_fee' => 50.00,
            'payment_status' => false,
            'last_payment_date' => now()->subMonth(),
            'next_payment_due' => now()->subDays(15),
        ]);

        $personaGuardia3 = Persona::create([
            'nombre' => 'Ana',
            'apellido_p' => 'Morales',
            'apellido_m' => 'Vega',
            'ci' => '55555555',
            'telefono' => '72345678',
            'foto' => '',
            'user_id' => $userGuardia3->id,
        ]);

        $guardia3 = Guardia::create([
            'estado' => '1',
            'fecha_ini' => '2025/05/15',
            'persona_id' => $personaGuardia3->id,
            'administrador_id' => $admin1->id,
        ]);

        // Guardia 4 - Sin pago
        $userGuardia4 = User::create([
            'name' => 'Guardia 4',
            'email' => 'guardia4@example.com',
            'password' => Hash::make('12345678'),
            'monthly_fee' => 50.00,
            'payment_status' => false,
            'last_payment_date' => now()->subMonth(),
            'next_payment_due' => now()->subDays(20),
        ]);

        $personaGuardia4 = Persona::create([
            'nombre' => 'Luis',
            'apellido_p' => 'Fernández',
            'apellido_m' => 'Castro',
            'ci' => '66666666',
            'telefono' => '73456789',
            'foto' => '',
            'user_id' => $userGuardia4->id,
        ]);

        $guardia4 = Guardia::create([
            'estado' => '1',
            'fecha_ini' => '2025/05/20',
            'persona_id' => $personaGuardia4->id,
            'administrador_id' => $admin1->id,
        ]);

        // Guardia 5 - Sin pago
        $userGuardia5 = User::create([
            'name' => 'Guardia 5',
            'email' => 'guardia5@example.com',
            'password' => Hash::make('12345678'),
            'monthly_fee' => 50.00,
            'payment_status' => false,
            'last_payment_date' => now()->subMonth(),
            'next_payment_due' => now()->subDays(25),
        ]);

        $personaGuardia5 = Persona::create([
            'nombre' => 'Sofia',
            'apellido_p' => 'Herrera',
            'apellido_m' => 'Ruiz',
            'ci' => '77777777',
            'telefono' => '74567890',
            'foto' => '',
            'user_id' => $userGuardia5->id,
        ]);

        $guardia5 = Guardia::create([
            'estado' => '1',
            'fecha_ini' => '2025/05/25',
            'persona_id' => $personaGuardia5->id,
            'administrador_id' => $admin1->id,
        ]);

        // Guardia 6 - Sin pago
        $userGuardia6 = User::create([
            'name' => 'Guardia 6',
            'email' => 'guardia6@example.com',
            'password' => Hash::make('12345678'),
            'monthly_fee' => 50.00,
            'payment_status' => false,
            'last_payment_date' => now()->subMonth(),
            'next_payment_due' => now()->subDays(30),
        ]);

        $personaGuardia6 = Persona::create([
            'nombre' => 'Miguel',
            'apellido_p' => 'Torres',
            'apellido_m' => 'Mendoza',
            'ci' => '88888888',
            'telefono' => '75678901',
            'foto' => '',
            'user_id' => $userGuardia6->id,
        ]);

        $guardia6 = Guardia::create([
            'estado' => '1',
            'fecha_ini' => '2025/05/30',
            'persona_id' => $personaGuardia6->id,
            'administrador_id' => $admin1->id,
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
