<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Connexion', ENT_QUOTES, 'UTF-8'); ?></title>
</head>
<body>
    <main>
        <h1><?= htmlspecialchars($title ?? 'Connexion', ENT_QUOTES, 'UTF-8'); ?></h1>

        <?php if (!empty($error)): ?>
            <p style="color: #b00020;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <p style="color: #0a5f2b;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <form method="post" action="/login">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>"
            >
            <label for="email">Email</label>
            <input
                id="email"
                name="email"
                type="email"
                required
                autocomplete="email"
                value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            >
            <button type="submit">Recevoir mon lien de connexion</button>
        </form>

        <p><a href="/">Retour a l'accueil</a></p>
    </main>
</body>
</html>
