<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guardia extends Authenticatable
{
    use HasFactory,SoftDeletes;

    protected $fillable=[
        'estado',
        'fecha_ini',
        'fecha_fin',
        'persona_id',
        'administrador_id',
    ];

    // Relación con Persona
    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    // Relación con Administrador (Un guardia pertenece a un administrador)
    public function administrador()
    {
        return $this->belongsTo(Administrador::class);
    }

    public function scopePersona($query,$id){
      if (is_null($id)) { return $query; }else{ return $query->where('persona_id',$id); }
    }

    /**
     * Verificar si el guardia tiene pago válido
     */
    public function hasValidPayment(): bool
    {
        // Consulta directa para obtener el usuario relacionado
        $user = \App\Models\User::whereHas('persona', function($query) {
            $query->where('id', $this->persona_id);
        })->first();
        
        return $user ? $user->hasValidPayment() : false;
    }
}
