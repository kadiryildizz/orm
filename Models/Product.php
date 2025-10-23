<?php
namespace ORM\Models;

use ORM\Model;

class Product extends Model {
    protected string $table = 'products';
    protected array $fillable = ['name','price'];
}