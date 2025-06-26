<?php

require 'functions.php';

session_start();

if (!isset($_SESSION['players'])) {
    $_SESSION['players'] = [['name' => 'Bank']];
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
}
