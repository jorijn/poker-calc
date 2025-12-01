<?php

require 'functions.php';

session_start();

$shareUrl = null;
$shareError = null;
$shareNotice = null;
$shareDirectory = __DIR__ . '/shares';

# load shared state from disk when ?share=... is present
if (isset($_GET['share'])) {
    $requestedShare = $_GET['share'];
    if (!preg_match('/^[A-Za-z0-9_-]+$/', $requestedShare)) {
        $shareError = 'Ongeldige share-link.';
    } else {
        $sharePath = $shareDirectory . '/' . $requestedShare . '.json';

        if (is_readable($sharePath)) {
            $shareData = json_decode(file_get_contents($sharePath), true);

            if (isset($shareData['players']) && is_array($shareData['players']) && isset($shareData['payments']) && is_array($shareData['payments'])) {
                $_SESSION['players'] = $shareData['players'];
                $_SESSION['payments'] = array_values($shareData['payments']);
                $_SESSION['settlement_strategy'] = $shareData['settlement_strategy'] ?? 'hub';
                $shareNotice = 'Spel geladen vanuit share-link ' . $requestedShare;
            } else {
                $shareError = 'Deel-link is beschadigd of onvolledig.';
            }
        } else {
            $shareError = 'Geen spel gevonden voor deze link.';
        }
    }
}

if (!isset($_SESSION['players'])) {
    $_SESSION['players'] = [['name' => 'Bank']];
}

if (!isset($_SESSION['payments']) || !is_array($_SESSION['payments'])) {
    $_SESSION['payments'] = [];
}

if (!isset($_SESSION['settlement_strategy'])) {
    $_SESSION['settlement_strategy'] = 'hub';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    # add player
    if (isset($_POST['action']) && $_POST['action'] === 'add_player' && isset($_POST['name']) && !empty($_POST['name'])) {
        $_SESSION['players'][] = ['name' => $_POST['name']];
    }

    # delete player
    if (isset($_POST['action']) && $_POST['action'] === 'delete_player' && isset($_POST['player']) && is_numeric($_POST['player']) && $_POST['player'] > 0) {
        unset($_SESSION['players'][$_POST['player']]);
    }

    # start again (reset)
    if (isset($_POST['action']) && $_POST['action'] === 'reset') {
        session_destroy();
        header('Location: index.php');
        exit;
    }

    # create payment
    if (isset($_POST['action']) && $_POST['action'] === 'payment' && isset($_POST['player']) && isset($_POST['receiver']) && isset($_POST['amount']) && is_numeric($_POST['amount'])) {
        $amount = round($_POST['amount'] * 100);
        $_SESSION['payments'][] = ['player' => $_POST['player'], 'receiver' => $_POST['receiver'], 'amount' => $amount];
    }

    # delete payment
    if (isset($_POST['action']) && $_POST['action'] === 'delete_payment' && isset($_POST['payment']) && is_numeric($_POST['payment'])) {
        unset($_SESSION['payments'][$_POST['payment']]);
    }

    # easy buy in, iterate over all players and create a payment from the bank (player index 0) to the player, argument is the amount
    if (isset($_POST['action']) && $_POST['action'] === 'start_game' && isset($_POST['amount']) && is_numeric($_POST['amount'])) {
        $amount = round($_POST['amount'] * 100);

        foreach ($_SESSION['players'] as $playerIndex => $playerData) {
            if ($playerIndex > 0) {
                $_SESSION['payments'][] = ['player' => 0, 'receiver' => $playerIndex, 'amount' => $amount];
            }
        }
    }

    # set settlement strategy
    if (isset($_POST['action']) && $_POST['action'] === 'set_settlement_strategy' && isset($_POST['strategy'])) {
        $validStrategies = ['hub', 'direct', 'minimal'];
        if (in_array($_POST['strategy'], $validStrategies)) {
            $_SESSION['settlement_strategy'] = $_POST['strategy'];
        }
    }

    # share snapshot to disk and expose link
    if (isset($_POST['action']) && $_POST['action'] === 'share_state') {
        if (!is_dir($shareDirectory)) {
            if (!mkdir($shareDirectory, 0777, true) && !is_dir($shareDirectory)) {
                $shareError = 'Kan share-map niet aanmaken.';
            }
        }

        if (!$shareError) {
            $snapshot = [
                'players' => $_SESSION['players'],
                'payments' => array_values($_SESSION['payments']),
                'settlement_strategy' => $_SESSION['settlement_strategy'] ?? 'hub',
                'version' => 1,
                'created_at' => time(),
            ];

            try {
                    $shareId = bin2hex(random_bytes(6));
                    $sharePath = $shareDirectory . '/' . $shareId . '.json';

                    $written = file_put_contents($sharePath, json_encode($snapshot, JSON_PRETTY_PRINT));
                    if ($written === false) {
                        $shareError = 'Opslaan van de share is mislukt.';
                    } else {
                        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                        $path = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
                        $shareUrl = $protocol . '://' . $host . $path . '?share=' . $shareId;
                        $shareNotice = 'Share-link aangemaakt.';
                    }
                } catch (Exception $e) {
                    $shareError = 'Kon geen unieke share-link maken.';
            }
        }
    }
}
