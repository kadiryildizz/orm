<?php
namespace ORM\Models;

use ORM\Model;

class Role extends Model {
    protected string $table = 'roles';
    protected array $fillable = ['name'];
}
