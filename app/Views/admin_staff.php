<?php

declare(strict_types=1);

$availableClasses = is_array($availableClasses ?? null) ? $availableClasses : [];
$availableStaffRoles = is_array($availableStaffRoles ?? null) ? $availableStaffRoles : [];
$staffItems = is_array($staffItems ?? null) ? $staffItems : [];
$createFormData = is_array($createFormData ?? null) ? $createFormData : [];
$editingStaffId = isset($editingStaffId) ? (int) $editingStaffId : null;
$editingFormData = is_array($editingFormData ?? null) ? $editingFormData : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Comptes professeurs et responsables', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_path('app.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="page-admin">
    <main class="page-shell">
        <nav class="top-nav surface">
            <div class="nav-cluster">
                <a class="nav-brand" href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Avenir Pro</a>
                <div class="nav-links">
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/'), ENT_QUOTES, 'UTF-8'); ?>">Tableau de bord</a>
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/admin/dashboard'), ENT_QUOTES, 'UTF-8'); ?>">Suivi college</a>
                    <a class="nav-link nav-link-current" href="<?= htmlspecialchars(app_path('/admin/staff'), ENT_QUOTES, 'UTF-8'); ?>">Comptes staff</a>
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/admin/internships'), ENT_QUOTES, 'UTF-8'); ?>">Moderation</a>
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/news'), ENT_QUOTES, 'UTF-8'); ?>">Mes news</a>
                    <a class="nav-link" href="<?= htmlspecialchars(app_path('/help'), ENT_QUOTES, 'UTF-8'); ?>">Aide</a>
                </div>
            </div>
            <div class="nav-actions">
                <form class="inline-form" method="post" action="<?= htmlspecialchars(app_path('/logout'), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="button-secondary">Me deconnecter</button>
                </form>
            </div>
        </nav>

        <section class="hero hero-split" style="margin-top: 1rem;">
            <div class="hero-copy">
                <p class="eyebrow">Comptes staff</p>
                <h1 class="hero-title"><?= htmlspecialchars($title ?? 'Comptes professeurs et responsables', ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="hero-text">Creer ici les comptes professeurs principaux et responsables de niveau. Ils se connecteront ensuite comme les autres utilisateurs, via Magic Link, sans mot de passe.</p>
                <div class="step-chip-row">
                    <span class="step-chip">Je cree le compte</span>
                    <span class="step-chip">Le staff recoit un lien</span>
                    <span class="step-chip">L'acces est borne au bon perimetre</span>
                </div>
            </div>
            <aside class="hero-panel">
                <p class="eyebrow">Perimetre</p>
                <ul class="offer-meta">
                    <li><strong>Professeur principal :</strong> acces limite a sa classe</li>
                    <li><strong>Responsable de niveau :</strong> acces global au suivi college</li>
                    <li><strong>Securite :</strong> aucun email eleve n'est affiche dans leurs ecrans</li>
                </ul>
            </aside>
        </section>

        <?php if (!empty($error)): ?>
            <p class="message message-error" style="margin-top: 1rem;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <p class="message message-success" style="margin-top: 1rem;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if (empty($accessDenied ?? false)): ?>
            <datalist id="known-classes">
                <?php foreach ($availableClasses as $classValue): ?>
                    <option value="<?= htmlspecialchars((string) $classValue, ENT_QUOTES, 'UTF-8'); ?>"></option>
                <?php endforeach; ?>
            </datalist>

            <section class="surface" style="margin-top: 1.4rem;">
                <h2 class="section-title">Creer un compte staff</h2>
                <p class="section-copy">Utilise de preference une adresse dediee a la personne. L'email servira ensuite de point d'entree pour recevoir le Magic Link.</p>
                <form method="post" action="<?= htmlspecialchars(app_path('/admin/staff/create'), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="field-grid">
                        <div class="field-group">
                            <label for="staff-email">Email</label>
                            <input id="staff-email" name="email" type="email" required value="<?= htmlspecialchars((string) ($createFormData['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="field-group">
                            <label for="staff-role">Role</label>
                            <select id="staff-role" name="role">
                                <?php foreach ($availableStaffRoles as $roleValue => $roleLabel): ?>
                                    <option value="<?= htmlspecialchars($roleValue, ENT_QUOTES, 'UTF-8'); ?>" <?= (string) ($createFormData['role'] ?? 'teacher') === $roleValue ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field-group">
                            <label for="staff-first-name">Prenom</label>
                            <input id="staff-first-name" name="first_name" type="text" required value="<?= htmlspecialchars((string) ($createFormData['first_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="field-group">
                            <label for="staff-last-name">Nom</label>
                            <input id="staff-last-name" name="last_name" type="text" required value="<?= htmlspecialchars((string) ($createFormData['last_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="field-group">
                            <label for="staff-managed-class">Classe geree</label>
                            <input
                                id="staff-managed-class"
                                name="managed_class"
                                type="text"
                                list="known-classes"
                                value="<?= htmlspecialchars((string) ($createFormData['managed_class'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </div>
                    </div>
                    <p class="form-help">Renseigner la classe uniquement pour un professeur principal. Pour un responsable de niveau, ce champ est ignore.</p>
                    <div class="inline-actions">
                        <button type="submit">Creer le compte</button>
                    </div>
                </form>
            </section>

            <section class="surface" style="margin-top: 1.5rem;">
                <h2 class="section-title">Comptes staff existants</h2>
                <?php if ($staffItems === []): ?>
                    <div class="empty-state">Aucun compte staff n'a encore ete configure.</div>
                <?php else: ?>
                    <div class="results-grid">
                        <?php foreach ($staffItems as $item): ?>
                            <?php
                            $itemId = (int) ($item['id'] ?? 0);
                            $isEditing = $editingStaffId !== null && $editingStaffId === $itemId && $editingFormData !== null;
                            $rowFormData = $isEditing
                                ? $editingFormData
                                : [
                                    'email' => (string) ($item['email'] ?? ''),
                                    'first_name' => (string) ($item['first_name'] ?? ''),
                                    'last_name' => (string) ($item['last_name'] ?? ''),
                                    'role' => (string) ($item['role'] ?? 'teacher'),
                                    'managed_class' => (string) ($item['managed_class'] ?? ''),
                                ];
                            $roleValue = (string) ($item['role'] ?? 'teacher');
                            ?>
                            <article class="offer-card">
                                <div class="offer-card-top">
                                    <span class="stat-badge stat-badge-soft">
                                        <?= htmlspecialchars($availableStaffRoles[$roleValue] ?? $roleValue, ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                    <span class="stat-badge stat-badge-soft">
                                        Cree le <?= htmlspecialchars(date('d/m/Y', strtotime((string) ($item['created_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </div>
                                <h3><?= htmlspecialchars(trim((string) (($item['first_name'] ?? '') . ' ' . ($item['last_name'] ?? ''))), ENT_QUOTES, 'UTF-8'); ?></h3>
                                <form method="post" action="<?= htmlspecialchars(app_path('/admin/staff/' . $itemId . '/update'), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Support\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="field-grid">
                                        <div class="field-group">
                                            <label for="email-<?= htmlspecialchars((string) $itemId, ENT_QUOTES, 'UTF-8'); ?>">Email</label>
                                            <input id="email-<?= htmlspecialchars((string) $itemId, ENT_QUOTES, 'UTF-8'); ?>" name="email" type="email" required value="<?= htmlspecialchars((string) ($rowFormData['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="field-group">
                                            <label for="role-<?= htmlspecialchars((string) $itemId, ENT_QUOTES, 'UTF-8'); ?>">Role</label>
                                            <select id="role-<?= htmlspecialchars((string) $itemId, ENT_QUOTES, 'UTF-8'); ?>" name="role">
                                                <?php foreach ($availableStaffRoles as $managedRoleValue => $managedRoleLabel): ?>
                                                    <option value="<?= htmlspecialchars($managedRoleValue, ENT_QUOTES, 'UTF-8'); ?>" <?= (string) ($rowFormData['role'] ?? '') === $managedRoleValue ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($managedRoleLabel, ENT_QUOTES, 'UTF-8'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="field-group">
                                            <label for="first-name-<?= htmlspecialchars((string) $itemId, ENT_QUOTES, 'UTF-8'); ?>">Prenom</label>
                                            <input id="first-name-<?= htmlspecialchars((string) $itemId, ENT_QUOTES, 'UTF-8'); ?>" name="first_name" type="text" required value="<?= htmlspecialchars((string) ($rowFormData['first_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="field-group">
                                            <label for="last-name-<?= htmlspecialchars((string) $itemId, ENT_QUOTES, 'UTF-8'); ?>">Nom</label>
                                            <input id="last-name-<?= htmlspecialchars((string) $itemId, ENT_QUOTES, 'UTF-8'); ?>" name="last_name" type="text" required value="<?= htmlspecialchars((string) ($rowFormData['last_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="field-group">
                                            <label for="managed-class-<?= htmlspecialchars((string) $itemId, ENT_QUOTES, 'UTF-8'); ?>">Classe geree</label>
                                            <input
                                                id="managed-class-<?= htmlspecialchars((string) $itemId, ENT_QUOTES, 'UTF-8'); ?>"
                                                name="managed_class"
                                                type="text"
                                                list="known-classes"
                                                value="<?= htmlspecialchars((string) ($rowFormData['managed_class'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                            >
                                        </div>
                                    </div>
                                    <p class="form-help">La classe est obligatoire pour un professeur principal, et ignoree pour un responsable de niveau.</p>
                                    <div class="inline-actions">
                                        <button type="submit">Enregistrer</button>
                                    </div>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
