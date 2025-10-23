<?php
namespace ORM\Models;

use ORM\Model;

class User extends Model {
    protected string $table = 'users';
    protected array $fillable = ['name', 'email', 'status', 'age'];


    public function posts(...$args) {
        return $this->hasMany(Post::class);
    }

    public function roles(...$args) {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
    }

    public function profile(...$args): ?array
    {
        return $this->hasOne(Profile::class, 'user_id', 'id');
    }
}
