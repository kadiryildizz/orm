<?php

require_once __DIR__ . '/vendor/autoload.php';

use ORM\Database;
use ORM\Models\User;
use ORM\Models\Post;
use ORM\Exceptions\QueryException;
use ORM\Exceptions\DatabaseException;
use ORM\Exceptions\ModelNotFoundException;

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>ORM Test Sonuçları</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', sans-serif;
            padding: 40px;
        }
        section {
            background: #fff;
            border-radius: 15px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        h2 {
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        pre {
            background: #f3f4f6;
            border-radius: 10px;
            padding: 12px 15px;
            overflow-x: auto;
            font-size: 14px;
        }
        .error-box {
            background: #ffe6e6;
            border: 1px solid #ffb3b3;
            padding: 15px;
            border-radius: 10px;
            color: #b30000;
            margin-bottom: 20px;
        }
        .success-box {
            background: #e6fff0;
            border: 1px solid #b3ffcc;
            padding: 15px;
            border-radius: 10px;
            color: #006622;
            margin-bottom: 20px;
        }
        .code-block {
            background: #1e1e1e;
            color: #c8e1ff;
            padding: 15px;
            border-radius: 10px;
            font-family: 'Consolas', monospace;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="container">

    <h1 class="mb-4 text-center">ORM Çalışması</h1>

    <?php
    set_exception_handler(function ($e) {
        echo "<div class='error-box'><strong>Hata:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    });

    try {
        $random = bin2hex(random_bytes(4)); // örn: 3f5e7c2a

        //1 CREATE
        echo "<section><h2>1 Kullanıcı Oluşturma (CREATE)</h2>";
        echo "<h6 class='text-muted'>📜 Sorgu:</h6>";
        echo "<div class='code-block'>\$user = User::create([
    'name' => 'Test_{$random}',
    'email' => 'test-{$random}@example.com',
    'status' => 'active',
    'age' => 30
]);</div>";

        $random = bin2hex(random_bytes(4));
        $user = User::create([
            'name' => "Test_{$random}",
            'email' => "test-{$random}@example.com",
            'status' => 'active',
            'age' => 30
        ]);

        echo "<h6 class='text-success mt-3'>Sonuç:</h6>";
        echo "<pre>" . print_r($user->toArray(), true) . "</pre>";
        echo "</section>";

        // FIND
        echo "<section><h2>2 Kullanıcıyı ID ile Bulma (FIND)</h2>";
        echo "<h6 class='text-muted'>📜 Sorgu:</h6>";
        echo "<div class='code-block'>\$foundUser = User::find(\$user->id);</div>";
        $foundUser = User::find($user->id);
        echo "<h6 class='text-success mt-3'>Sonuç:</h6>";
        echo "<pre>" . print_r($foundUser->toArray(), true) . "</pre>";
        echo "</section>";

        // WHERE
        echo "<section><h2>3 Filtreleme (WHERE)</h2>";
        echo "<h6 class='text-muted'>📜 Sorgu:</h6>";
        echo "<div class='code-block'>\$users = User::where('status', 'active')
    ->where('age', '>', 18)
    ->get();</div>";

        $users = User::where('status', 'active')->where('age', '>', 18)->get();
        echo "<h6 class='text-success mt-3'>Sonuç:</h6>";
        foreach ($users as $u) {
            echo "<pre>" . print_r($u, true) . "</pre>";
        }
        echo "</section>";

        // 4 UPDATE
        echo "<section><h2>4 Güncelleme (UPDATE)</h2>";
        echo "<h6 class='text-muted'>📜 Sorgu:</h6>";
        echo "<div class='code-block'>User::update(\$user->id, ['name' => 'Veli']);</div>";

        User::update($user->id, ['name' => 'Veli']);
        $updatedUser = User::find($user->id);
        echo "<h6 class='text-success mt-3'>Güncellenmiş Kayıt:</h6>";
        echo "<pre>" . print_r($updatedUser->toArray(), true) . "</pre>";
        echo "</section>";

        //RELATIONSHIPS
        echo "<section><h2>5 İlişkiler (hasMany / belongsTo)</h2>";
        echo "<h6 class='text-muted'>📜 Sorgu:</h6>";
        echo "<div class='code-block'>
\$post1 = Post::create(['user_id' => 1, 'title' => 'İlk Post', 'body' => 'İçerik 1']);
\$user = User::find(1);
\$posts = \$user->posts();</div>";

        $post1 = Post::create(['user_id' => 1, 'title' => 'İlk Post', 'content' => 'İçerik 1']);
        $user = User::find(1);
        $posts = $user->posts();
        echo "<h6 class='text-success mt-3'>Kullanıcının Postları:</h6>";
        foreach ($posts as $p) {
            echo "<pre>" . print_r($p, true) . "</pre>";
        }
        echo "</section>";


        echo "<section><h2>hasMany (User ➜ Posts)</h2>";
        echo "<h6 class='text-muted'>📜 Sorgu:</h6>";
        echo "<div class='code-block'>
\$user = User::find(1);
\$posts = \$user->posts();</div>";
        $user = User::find(1);
        $posts = $user->posts();
        if ($posts) {
            echo "<h6 class='text-success mt-3'>Kullanıcının Postları:</h6>";
            foreach ($posts as $p) {
                echo "<pre>" . print_r($p, true) . "</pre>";
            }
        } else {
            echo "<div class='error-box'>Bu kullanıcıya ait post bulunamadı.</div>";
        }
        echo "</section>";

        echo "<section><h2>hasOne (User ➜ Profile)</h2>";
        echo "<h6 class='text-muted'>📜 Sorgu:</h6>";
        echo "<div class='code-block'>
\$user = User::find(1);
\$profile = \$user->profile();</div>";

        $profile = $user->profile();

        if ($profile) {
            echo "<h6 class='text-success mt-3'>Kullanıcı Profili:</h6>";
            echo "<pre>" . print_r($profile, true) . "</pre>";
        } else {
            echo "<div class='error-box'>Bu kullanıcıya ait profil bulunamadı.</div>";
        }
        echo "</section>";

        echo "<section><h2>belongsToMany</h2>";
        echo "<h6 class='text-muted'>📜 Sorgu:</h6>";
        echo "<div class='code-block'>
\$user = User::find(1);
\$roles = \$user->roles();</div>";

        $roles = $user->roles();

        if ($roles && count($roles) > 0) {
            echo "<h6 class='text-success mt-3'>Kullanıcının Rolleri:</h6>";
            foreach ($roles as $r) {
                echo "<pre>" . print_r($r, true) . "</pre>";
            }
        } else {
            echo "<div class='error-box'>Bu kullanıcıya ait rol bulunamadı.</div>";
        }
        echo "</section>";

        echo "<section><h2>belongsTo</h2>";
        echo "<h6 class='text-muted'>📜 Sorgu:</h6>";
        echo "<div class='code-block'>
\$post = Post::find(1);
\$owner = \$post->user();</div>";

        $post = Post::find(1);
        $owner = $post ? $post->user() : null;

        if ($owner) {
            echo "<h6 class='text-success mt-3'>Postun Sahibi:</h6>";
            echo "<pre>" . print_r($owner, true) . "</pre>";
        } else {
            echo "<div class='error-box'>Post bulunamadı veya ilişkili kullanıcı yok.</div>";
        }
        echo "</section>";

        echo "<section><h2>Eager Loading (with)</h2>";
        echo "<h6 class='text-muted'>📜 Sorgu:</h6>";
        echo "<div class='code-block'>
\$posts = Post::with('user')->get();</div>";

        $posts = Post::with('user')->get();

        if ($posts) {
            echo "<h6 class='text-success mt-3'>Postlar ve Kullanıcı Bilgileri:</h6>";
            foreach ($posts as $p) {
                echo "<pre>" . print_r([
                        'post' => $p,
                    ], true) . "</pre>";
            }
        }
        echo "</section>";

        // 8️⃣ QUERY BUILDER (Model'den bağımsız)
        echo "<section><h2>6 Query Builder (Model'den Bağımsız)</h2>";
        echo "<h6 class='text-muted'>📜 Sorgu:</h6>";
        echo "<div class='code-block'>
        \$query = (new \\ORM\\QueryBuilder());
        \$result = \$query->table('users')
            ->select(['id', 'name', 'email', 'status'])
            ->where('age', '>', 20)
            ->limit(5)
            ->get();</div>";

        $pdo = $pdo ?? Database::getConnection();
        $qb = new \ORM\QueryBuilder($pdo);
        $result = $qb->table('users')
            ->select(['id', 'name', 'email', 'status'])
            ->where('age', '>', 20)
            ->limit(5)
            ->get();

        echo "<h6 class='text-success mt-3'>Sorgu Sonucu:</h6>";
        if (!empty($result)) {
            echo "<pre>" . print_r($result, true) . "</pre>";
        } else {
            echo "<div class='error-box'>Bu kritere uyan kullanıcı bulunamadı.</div>";
        }
        echo "</section>";


        //COUNT / EXISTS
        echo "<section><h2>7 Sayım & Kontrol</h2>";
        echo "<h6 class='text-muted'>📜 Sorgu:</h6>";
        echo "<div class='code-block'>
\$count = User::where('status', 'active')->count();
\$exists = User::where('email', 'ali@gmail.com')->exists();</div>";
        $activeCount = User::where('status', 'active')->count();
        $exists = User::where('email', 'ali@gmail.com')->exists();
        echo "<p>📌 Aktif kullanıcı sayısı: <strong>$activeCount</strong></p>";
        echo "<p>✅ Ali var mı? <strong>" . ($exists ? 'Evet' : 'Hayır') . "</strong></p>";
        echo "</section>";

        // DELETE
        echo "<section><h2>8 Silme (DELETE)</h2>";
        echo "<h6 class='text-muted'>📜 Sorgu:</h6>";
        echo "<div class='code-block'>Post::delete(\$post1->id);</div>";
        $deletedPost = Post::delete($post1->id);
        echo "<h6 class='text-success mt-3'>Sonuç:</h6>";
        echo "<pre>" . var_export($deletedPost, true) . "</pre>";
        echo "</section>";

    } catch (ModelNotFoundException $e) {
        echo "<div class='error-box'>Not Found: " . htmlspecialchars($e->getMessage()) . "</div>";
    } catch (QueryException $e) {
        echo "<div class='error-box'>DB Query Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    } catch (DatabaseException $e) {
        echo "<div class='error-box'>DB Connection Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    } catch (InvalidArgumentException $e) {
        echo "<div class='error-box'>Invalid Argument: " . htmlspecialchars($e->getMessage()) . "</div>";
    } catch (Exception $e) {
        echo "<div class='error-box'>General Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    ?>

</div>
</body>
</html>
