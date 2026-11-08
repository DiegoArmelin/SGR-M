<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Perfis de acesso permitidos no SGR-M.
     */
    public const PERFIL_ADMINISTRADOR = 'administrador';
    public const PERFIL_GESTOR = 'gestor';
    public const PERFIL_TECNICO = 'tecnico';

    /**
     * Atributos que podem ser preenchidos em massa.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'perfil',
    ];

    /**
     * Atributos ocultos na serialização.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Conversão automática dos atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Verifica se o usuário é administrador.
     */
    public function isAdministrador(): bool
    {
        return $this->perfil === self::PERFIL_ADMINISTRADOR;
    }

    /**
     * Verifica se o usuário é gestor.
     */
    public function isGestor(): bool
    {
        return $this->perfil === self::PERFIL_GESTOR;
    }

    /**
     * Verifica se o usuário é técnico.
     */
    public function isTecnico(): bool
    {
        return $this->perfil === self::PERFIL_TECNICO;
    }

    /**
     * Verifica se o usuário possui um dos perfis informados.
     */
    public function temPerfil(string ...$perfis): bool
    {
        return in_array($this->perfil, $perfis, true);
    }
}
