<?php
namespace ORM\Models;

use ORM\Model;
use ORM\Models\User;

class Post extends Model {
    protected string $table = 'posts';
    protected array $fillable = ['title', 'content', 'user_id'];

    public function user(...$args): mixed
    {
        return $this->belongsTo(User::class, ...$args);
    }
}
