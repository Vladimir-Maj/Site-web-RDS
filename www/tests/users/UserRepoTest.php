<?php
namespace App\Tests;

class UserRepoTest extends MySQLTestCase 
{
    /**
     * TEST: The Integer Trap
     * Purpose: Ensure the code doesn't crash if someone passes a 
     * numeric ID (old habit) to a UUID-based system.
     */
    public function testRejectionOfNumericIds() 
    {
        $repo = new \App\Repository\UserRepository(self::$pdo);
        // This should not throw a PDO error; it should just return null
        $user = $repo->findById("123"); 
        $this->assertNull($user);
        $user = $repo->findByEmail("nonexistent@example.com");
        $this->assertNull($user);
    }

    public function testRejectionOfNonNumericIds()
    {
        $repo = new \App\Repository\UserRepository(self::$pdo);
        // This should not throw a PDO error; it should just return null
        $user = $repo->findById("not-a-uuid"); 
        $this->assertNull($user);
    }

    public function testInsert()
    {
        $repo = new \App\Repository\UserRepository(self::$pdo);
        $user = \App\Models\UserModel::fromArray([
            'email' => 'test@example.com',
            'password' => 'hashedpassword',
            'first_name' => 'Test',
            'last_name' => 'User',
            'is_active' => true,
        ]);

        $repo->push($user);
        $fetched = $repo->findByEmail('test@example.com');

        $this->assertNotNull($fetched);
        $this->assertEquals($fetched->email, 'test@example.com');
        $this->assertEquals($fetched->first_name, 'Test');
        $this->assertEquals($fetched->last_name, 'User');
        $this->assertTrue($fetched->is_active);
        $this->assertEquals($fetched->password, 'hashedpassword');
    }
    /**
     * TEST: The "Zombie" Student
     * Purpose: Try to create a student for a user that doesn't exist.
     * Expectation: DB Foreign Key must reject this.
     */
    public function testOrphanStudentConstraint() 
    {
        $repo = new \App\Repository\UserRepository(self::$pdo);
        $randomHex = bin2hex(random_bytes(16));

        $this->expectException(\PDOException::class);
        // MySQL will throw an error because user_id UNHEX($randomHex) is not in user table
        $repo->makeStudent($randomHex);
    }

    /**
     * TEST: Duplicate Email Attack
     * Purpose: Test the UNIQUE constraint on the email column.
     */
    public function testDuplicateEmailConstraint()
    {
        $repo = new \App\Repository\UserRepository(self::$pdo);
        
        $u1 = \App\Models\UserModel::fromArray(['email' => 'evil@twins.com', 'password' => '123']);
        $repo->push($u1);

        $this->expectException(\PDOException::class);
        // This should fail because 'email' is UNIQUE in your CREATE TABLE statement
        $repo->push($u1);
    }

    /**
     * TEST: UUIDv7 Trigger Verification
     * Purpose: Ensure the DB is actually generating IDs correctly.
     */
    public function testTriggerGeneratesValidUuid()
    {
        $repo = new \App\Repository\UserRepository(self::$pdo);
        $u = \App\Models\UserModel::fromArray(['email' => 'trigger@test.com', 'password' => '123']);
        
        $repo->push($u);
        
        $stmt = self::$pdo->query("SELECT HEX(id) as id FROM user WHERE email = 'trigger@test.com'");
        $id = $stmt->fetchColumn();

        $this->assertEquals(32, strlen($id), "The trigger failed to generate a 32-char Hex UUID.");
        // UUIDv7 check: The 13th character should be '7'
        $this->assertEquals('7', $id[12], "Generated UUID is not version 7.");
    }
}