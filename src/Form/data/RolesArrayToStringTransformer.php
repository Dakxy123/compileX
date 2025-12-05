<?php
namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class RolesArrayToStringTransformer implements DataTransformerInterface
{
    public function transform($roles): ?string
    {
        // array to string: take first role
        if (is_array($roles) && count($roles) > 0) {
            return $roles[0];
        }
        return null;
    }

    public function reverseTransform($role): array
    {
        // string to array
        return $role ? [$role] : [];
    }
}
