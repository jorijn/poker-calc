<?php

/**
 * this function should return the balance of a player
 */
function getBalanceOfPlayer($playerIndex)
{
  $balance = 0;

  if (isset($_SESSION['payments']) && is_array($_SESSION['payments'])) {
    foreach ($_SESSION['payments'] as $paymentData) {
      if ($paymentData['player'] == $playerIndex) {
        $balance -= $paymentData['amount'];
      }
      if ($paymentData['receiver'] == $playerIndex) {
        $balance += $paymentData['amount'];
      }
    }
    return $balance;
  }

  return $balance;
}

/**
 * this function should sum up all players and their balances, if the sum isn't 0, there was a mistake in the game
 * @return bool
 */
function getTheSumOfTheGame(): int
{
  $sum = 0;
  foreach ($_SESSION['players'] as $playerIndex => $playerData) {
    $sum += getBalanceOfPlayer($playerIndex);
  }
  return $sum;
}

/**
 * function to check if the game can be closed, check if the balance of the bank is zero, and there are more than 0 payments
 */
function needToSettle(): bool
{
  $paymentsCount = count($_SESSION['payments'] ?? []);
  if (getBalanceOfPlayer(0) == 0 && $paymentsCount) {
    return true;
  }
  return false;
}

/**
 * Calculate the settlement steps. This function should return an array with the settlement steps. The logic
 * is as follows: Negative balance indicates a claim, positive balance indicates a debt. The steps should be
 * calculated so that there is as little transactions as possible.
 */
function getSettlementSteps()
{
  $settlementSteps = [];

  $players = $_SESSION['players'];
  $payments = $_SESSION['payments'];

  $balances = [];
  foreach ($players as $playerIndex => $playerData) {
    $balances[$playerIndex] = getBalanceOfPlayer($playerIndex);
  }

  while (true) {
    $maxDebtPlayerIndex = array_search(max($balances), $balances);
    $maxClaimPlayerIndex = array_search(min($balances), $balances);

    if ($balances[$maxDebtPlayerIndex] == 0 || $balances[$maxClaimPlayerIndex] == 0) {
      break;
    }

    $settlementSteps[] = [
      'from' => $maxDebtPlayerIndex,
      'to' => $maxClaimPlayerIndex,
      'amount' => abs($balances[$maxClaimPlayerIndex]),
    ];

    $balances[$maxDebtPlayerIndex] -= abs($balances[$maxClaimPlayerIndex]);
    $balances[$maxClaimPlayerIndex] = 0;
  }

  return $settlementSteps;
}
