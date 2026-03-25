<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\NotificationRepository;
use App\Support\SessionManager;
use PDO;

final class NotificationController
{
    private NotificationRepository $notifications;

    public function __construct(PDO $pdo)
    {
        $this->notifications = new NotificationRepository($pdo);
    }

    public function index(): void
    {
        $currentUser = SessionManager::currentUser();

        if ($currentUser === null) {
            app_redirect('/login?' . http_build_query([
                'return_to' => '/news',
            ]));
        }

        $title = 'Mes news';
        $items = $this->notifications->findAllByUserId((int) $currentUser['id']);
        $unreadCount = $this->notifications->countUnreadByUserId((int) $currentUser['id']);
        $error = null;
        $success = ($_GET['status'] ?? null) === 'read-all'
            ? 'Toutes les news ont ete marquees comme lues.'
            : null;

        require __DIR__ . '/../Views/notifications.php';
    }

    public function markAllRead(): void
    {
        $currentUser = SessionManager::currentUser();

        if ($currentUser === null) {
            app_redirect('/login?' . http_build_query([
                'return_to' => '/news',
            ]));
        }

        $this->notifications->markAllAsReadByUserId((int) $currentUser['id']);

        app_redirect('/news?status=read-all');
    }
}
