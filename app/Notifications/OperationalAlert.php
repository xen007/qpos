<?php
namespace App\Notifications;

use Illuminate\Notifications\Notification;

// Persisted exclusively by the durable outbox worker, not by a second sender.
final class OperationalAlert extends Notification {}
