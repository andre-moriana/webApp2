<?php
// Simuler une session utilisateur
session_start();
$_SESSION['user'] = [
    'id' => 1,
    'is_admin' => true,
    'isAdmin' => true
];

// Messages de test
$chatMessages = [
    [
        '_id' => 1,
        'author' => [
            '_id' => 1,
            'name' => 'Gémenos'
        ],
        'content' => 'Hello',
        'createdAt' => '2025-07-30 14:50:24'
    ],
    [
        '_id' => 2,
        'author' => [
            '_id' => 2,
            'name' => 'Test User'
        ],
        'content' => 'Bonjour !',
        'createdAt' => '2025-07-30 14:51:00'
    ]
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .message {
            max-width: 70%;
            padding: 1rem;
            border-radius: 1rem;
            margin-bottom: 1rem;
        }

        .message-sent {
            background-color: #007bff;
            color: white;
            margin-left: auto;
        }

        .message-received {
            background-color: #e9ecef;
            color: #212529;
            margin-right: auto;
        }

        .message-author {
            font-weight: bold;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }

        .message-time {
            font-size: 0.75rem;
            opacity: 0.8;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Test Chat</h5>
            </div>
            <div class="card-body">
                <div id="messages-container" class="messages-container">
                    <?php foreach ($chatMessages as $message): ?>
                        <div class="d-flex <?php echo ($message['author']['_id'] == $_SESSION['user']['id']) ? 'justify-content-end' : 'justify-content-start'; ?> mb-3">
                            <div class="message <?php echo ($message['author']['_id'] == $_SESSION['user']['id']) ? 'message-sent' : 'message-received'; ?>">
                                <div class="message-author"><?php echo htmlspecialchars($message['author']['name']); ?></div>
                                <div class="message-content"><?php echo nl2br(htmlspecialchars($message['content'])); ?></div>
                                <div class="message-time"><?php echo (new DateTime($message['createdAt']))->format('d/m/Y H:i:s'); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 