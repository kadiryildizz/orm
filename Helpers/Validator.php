<?php
namespace ORM\Helpers;

class Validator
{
    public static function validateTableCheckName(string $name): bool
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            return false;
        }
        return true;
    }

    public static function validateTableClassCheckName(string $name): bool
    {
        if (!preg_match('/^[a-zA-Z0-9_\\\\]+$/', $name)) {
            return false;
        }
        return true;
    }




}
