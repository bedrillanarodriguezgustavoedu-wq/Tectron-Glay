<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'estado',
        'galy_user_id',
        'galy_es_admin',
        'dni',
        'telefono',
        'foto',
        'google_id',
        'checkout_privacy_accepted_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'dni',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'galy_es_admin' => 'boolean',
            'dni' => 'encrypted',
            'checkout_privacy_accepted_at' => 'datetime',
        ];
    }

    public function resolveGalyUserId(): ?int
    {
        if ($this->galy_user_id !== null) {
            return (int) $this->galy_user_id;
        }

        $email = strtolower(trim((string) $this->email));
        if ($email === '' || ! Schema::connection('galy')->hasTable('usuarios')) {
            return null;
        }

        $columns = Schema::connection('galy')->getColumnListing('usuarios');
        $canonicalUser = DB::connection('galy')
            ->table('usuarios')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first(in_array('es_admin', $columns, true) ? ['id', 'es_admin'] : ['id']);

        if (! $canonicalUser) {
            return null;
        }

        $alreadyLinked = static::query()
            ->where('galy_user_id', $canonicalUser->id)
            ->whereKeyNot($this->getKey())
            ->exists();

        if ($alreadyLinked) {
            throw new RuntimeException('La cuenta GALY ya está vinculada a otro usuario web.');
        }

        $this->forceFill([
            'galy_user_id' => (int) $canonicalUser->id,
            'galy_es_admin' => (bool) ($canonicalUser->es_admin ?? false),
        ])->save();

        return (int) $canonicalUser->id;
    }

    public function productoFavoritos()
    {
        return $this->hasMany(ProductoFavorito::class, 'usuario_id', 'galy_user_id');
    }

    public function carritos()
    {
        return $this->hasMany(Carrito::class, 'usuario_id', 'galy_user_id');
    }

    public function ordenes()
    {
        return $this->hasMany(Orden::class, 'usuario_id', 'galy_user_id');
    }

    public function direcciones()
    {
        return $this->hasMany(Direccion::class, 'usuario_id');
    }

    public function resenas()
    {
        return $this->hasMany(Resena::class, 'usuario_id', 'galy_user_id');
    }

    /**
     * El avatar del cliente es SU foto, no el logo de la tienda: si falta o el
     * archivo ya no está, la vista debe poder caer en las iniciales sin romperse.
     */
    public function getRutaFotoAttribute(): ?string
    {
        $foto = trim((string) $this->foto);

        if ($foto === '') {
            return null;
        }

        return Storage::disk('public')->exists($foto) ? asset('storage/'.$foto) : null;
    }

    public function getInicialesAttribute(): string
    {
        $partes = preg_split('/\s+/', trim((string) $this->name)) ?: [];

        $iniciales = collect($partes)
            ->filter()
            ->take(2)
            ->map(fn ($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))
            ->implode('');

        return $iniciales !== '' ? $iniciales : 'GA';
    }
}
