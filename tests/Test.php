<?php

use PHPUnit\Framework\TestCase;
use ORM\Models\User;
use ORM\Models\Post;
use ORM\Database;

final class Test extends TestCase
{
    public function testCreateUser(): void
    {
        $user = User::create([
            'name' => "Ali",
            'email' => "ali@gmail.com",
            'status' => 'active',
            'age' => 30
        ]);

        $this->assertIsObject($user);
        $this->assertEquals("Ali", $user->name);
    }

    public function testFindUser(): void
    {
        $user = User::find(1);
        $this->assertEquals(1, $user->id);
        $this->assertEquals('Ali', $user->name);
    }

    public function testWhereQuery(): void
    {
        $users = User::where('status', 'active')->get();
        $this->assertIsArray($users);
        $this->assertGreaterThanOrEqual(1, count($users));
    }

    public function testUpdateUser(): void
    {
        User::update(1, ['name' => 'Veli']);
        $user = User::find(1);
        $this->assertEquals('Veli', $user->name);
    }


    public function testHasManyRelation(): void
    {
        $user = User::find(1);

        $post1 = Post::create(['user_id' => $user->id, 'title' => 'Hello', 'body' => 'World'])->toArray();

        $post2 = Post::create(['user_id' => $user->id, 'title' => 'Second Post', 'body' => 'Content'])->toArray();

        $foundPosts = $user->posts();
        $this->assertCount(2, $foundPosts);
        $this->assertEquals($post1->title, $foundPosts[0]->title);
        $this->assertEquals($post2->title, $foundPosts[1]->title);
    }


    public function testCountAndExists(): void
    {
        $count = User::where('status', 'active')->count();
        $this->assertGreaterThan(0, $count);

        $exists = User::where('status','active')->exists();
        $this->assertTrue($exists);
    }

    public function testToArrayAndJson(): void
    {
        $user = User::find(1);
        $arr = $user->toArray();
        $this->assertIsArray($arr);
        $this->assertArrayHasKey('email', $arr);

        $json = $user->toJson();
        $this->assertJson($json);
    }

    public function testQueryBuilderBasicSelect(): void
    {
        $pdo = Database::getConnection();
        $qb = new \ORM\QueryBuilder($pdo);

        $results = $qb->table('users')
            ->select(['id', 'name', 'email'])
            ->where('status', '=', 'active')
            ->orderBy('id', 'DESC')
            ->limit(5)
            ->get();

        $this->assertIsArray($results);
        $this->assertLessThanOrEqual(5, count($results));
        $this->assertArrayHasKey('name', $results[0]);
    }


    public static function setUpBeforeClass(): void
    {
        $pdo = Database::getConnection();
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("TRUNCATE TABLE role_user;");
        $pdo->exec("TRUNCATE TABLE roles;");
        $pdo->exec("TRUNCATE TABLE profiles;");
        $pdo->exec("TRUNCATE TABLE users;");
        $pdo->exec("TRUNCATE TABLE posts;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }


}
