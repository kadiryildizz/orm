<?php
namespace ORM\Models;

use ORM\Model;

class Profile extends Model
{
    protected string $table = 'profiles';
    protected array $fillable = ['user_id', 'bio', 'avatar'];

    public function user(...$args): ?array
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
