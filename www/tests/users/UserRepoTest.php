<?php
namespace App\Tests;

class UserRepoTest extends MySQLTestCase
{
    public function testFindByIdWithInvalidValue()
    {
        $repo = new \App\Repository\UserRepository(self::$pdo);

        $user = $repo->findById("abc");
        $this->assertNull($user);
    }

    public function testFindByEmailNotFound()
    {
        $repo = new \App\Repository\UserRepository(self::$pdo);

        $user = $repo->findByEmail("nonexistent@example.com");
        $this->assertNull($user);
    }

    public function testInsert()
    {
        $repo = new \App\Repository\UserRepository(self::$pdo);

        $user = \App\Models\UserModel::fromArray([
            'email_user' => 'test@example.com',
            'password' => 'hashedpassword',
            'first_name_user' => 'Test',
            'last_name_user' => 'User',
            'is_active_user' => true,
        ]);

        $repo->push($user);

        $fetched = $repo->findByEmail('test@example.com');

        $this->assertNotNull($fetched);
        $this->assertEquals('test@example.com', $fetched->email_user);
        $this->assertEquals('Test', $fetched->first_name_user);
        $this->assertEquals('User', $fetched->last_name_user);
        $this->assertTrue($fetched->is_active_user);
        $this->assertEquals('hashedpassword', $fetched->password);
    }

    public function testOrphanStudentConstraint()
    {
        $repo = new \App\Repository\UserRepository(self::$pdo);

        $this->expectException(\PDOException::class);
        $repo->makeStudent(999999);
    }

    public function testDuplicateEmailConstraint()
    {
        $repo = new \App\Repository\UserRepository(self::$pdo);

        $u1 = \App\Models\UserModel::fromArray([
            'email_user' => 'evil@twins.com',
            'password' => '123'
        ]);

        $repo->push($u1);

        $u2 = \App\Models\UserModel::fromArray([
            'email_user' => 'evil@twins.com',
            'password' => '456'
        ]);

        $this->expectException(\PDOException::class);
        $repo->push($u2);
    }
}