<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $user = new User();

        $user->setNom('Doe');
        $user->setPrenom('John');
        $user->setEmail('john.doe@example.com');
        $user->setPassword('hashed_password');
        $user->setRoles(['ROLE_ADMIN']);

        $this->assertSame('Doe', $user->getNom());
        $this->assertSame('John', $user->getPrenom());
        $this->assertSame('john.doe@example.com', $user->getEmail());
        $this->assertSame('hashed_password', $user->getPassword());

        // getRoles() doit contenir au moins ROLE_USER
        $roles = $user->getRoles();
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles);

        // getUserIdentifier() = email
        $this->assertSame('john.doe@example.com', $user->getUserIdentifier());
    }
}
