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
 * Format a cent amount to a euro string, optionally prefixed with +/-
 */
function formatEuro(int $amountInCents, bool $forceSign = false): string
{
  $sign = '';

  if ($amountInCents < 0) {
    $sign = '-';
  } elseif ($forceSign && $amountInCents > 0) {
    $sign = '+';
  }

  return $sign . '€ ' . number_format(abs($amountInCents) / 100, 2, ',', '.');
}

/**
 * Returns human friendly metadata for a balance so the UI can clarify debt/credit
 */
function getBalanceMeta(int $balance): array
{
  if ($balance > 0) {
    return [
      'label' => 'Te betalen',
      'tone' => 'debt',
      'explanation' => 'heeft meer ontvangen dan ingelegd',
    ];
  }

  if ($balance < 0) {
    return [
      'label' => 'Te ontvangen',
      'tone' => 'credit',
      'explanation' => 'heeft meer ingelegd dan ontvangen',
    ];
  }

  return [
    'label' => 'In evenwicht',
    'tone' => 'even',
    'explanation' => 'staat precies op nul',
  ];
}

/**
 * Build a timeline of balances before/after each payment (bank included)
 */
function getBalanceTimeline(): array
{
  $timeline = [];

  if (!isset($_SESSION['payments']) || !is_array($_SESSION['payments'])) {
    return $timeline;
  }

  $balances = [];
  foreach ($_SESSION['players'] as $playerIndex => $playerData) {
    $balances[$playerIndex] = 0;
  }

  foreach ($_SESSION['payments'] as $paymentIndex => $paymentData) {
    $before = $balances;

    if (isset($balances[$paymentData['player']])) {
      $balances[$paymentData['player']] -= $paymentData['amount'];
    }
    if (isset($balances[$paymentData['receiver']])) {
      $balances[$paymentData['receiver']] += $paymentData['amount'];
    }

    $timeline[$paymentIndex] = [
      'before' => $before,
      'after' => $balances,
    ];
  }

  return $timeline;
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
 * Calculate the settlement steps using the selected strategy
 */
function getSettlementSteps()
{
  $strategy = $_SESSION['settlement_strategy'] ?? 'hub';
  
  switch ($strategy) {
    case 'minimal':
      return getMinimalTransactionsSettlement();
    case 'hub':
      return getHubBasedSettlement();
    case 'direct':
      return getDirectSettlement();
    default:
      return getHubBasedSettlement();
  }
}

/**
 * Original algorithm - minimizes number of transactions
 */
function getMinimalTransactionsSettlement()
{
  $settlementSteps = [];
  $balances = [];
  
  foreach ($_SESSION['players'] as $playerIndex => $playerData) {
    $balances[$playerIndex] = getBalanceOfPlayer($playerIndex);
  }

  while (true) {
    $maxDebtPlayerIndex = array_search(max($balances), $balances);
    $maxClaimPlayerIndex = array_search(min($balances), $balances);

    if ($balances[$maxDebtPlayerIndex] <= 0.01 || $balances[$maxClaimPlayerIndex] >= -0.01) {
      break;
    }

    $amount = min($balances[$maxDebtPlayerIndex], abs($balances[$maxClaimPlayerIndex]));
    
    $settlementSteps[] = [
      'from' => $maxDebtPlayerIndex,
      'to' => $maxClaimPlayerIndex,
      'amount' => $amount,
    ];

    $balances[$maxDebtPlayerIndex] -= $amount;
    $balances[$maxClaimPlayerIndex] += $amount;
  }

  return $settlementSteps;
}

/**
 * Hub-based settlement - uses players with largest claims/debts as hubs
 */
function getHubBasedSettlement()
{
  $settlementSteps = [];
  $balances = [];
  
  foreach ($_SESSION['players'] as $playerIndex => $playerData) {
    $balances[$playerIndex] = getBalanceOfPlayer($playerIndex);
  }
  
  // Skip bank (index 0)
  unset($balances[0]);
  
  // Find natural hubs (players with largest claims or debts)
  $creditors = array_filter($balances, fn($b) => $b < -0.01);
  $debtors = array_filter($balances, fn($b) => $b > 0.01);
  
  // Sort creditors by claim size (largest first)
  arsort($creditors);
  $creditors = array_reverse($creditors, true);
  
  // Sort debtors by debt size (largest first)
  arsort($debtors);
  
  // Process settlements through largest creditor first
  foreach ($creditors as $creditorIndex => $creditorBalance) {
    foreach ($debtors as $debtorIndex => $debtorBalance) {
      if ($creditorBalance >= -0.01 || $debtorBalance <= 0.01) {
        continue;
      }
      
      $amount = min($debtorBalance, abs($creditorBalance));
      
      $settlementSteps[] = [
        'from' => $debtorIndex,
        'to' => $creditorIndex,
        'amount' => $amount,
      ];
      
      $debtors[$debtorIndex] -= $amount;
      $creditors[$creditorIndex] += $amount;
      $creditorBalance += $amount;
    }
  }
  
  return $settlementSteps;
}

/**
 * Direct settlement - prioritizes direct payments between players
 */
function getDirectSettlement()
{
  $settlementSteps = [];
  $balances = [];
  
  foreach ($_SESSION['players'] as $playerIndex => $playerData) {
    $balances[$playerIndex] = getBalanceOfPlayer($playerIndex);
  }
  
  // Skip bank (index 0)
  unset($balances[0]);
  
  // First, net off any mutual debts
  $processed = [];
  foreach ($balances as $playerA => $balanceA) {
    foreach ($balances as $playerB => $balanceB) {
      if ($playerA >= $playerB) continue;
      if (isset($processed["$playerA-$playerB"])) continue;
      
      // If one owes and one is owed
      if (($balanceA > 0.01 && $balanceB < -0.01) || ($balanceA < -0.01 && $balanceB > 0.01)) {
        $from = $balanceA > 0 ? $playerA : $playerB;
        $to = $balanceA > 0 ? $playerB : $playerA;
        $amount = min(abs($balances[$from]), abs($balances[$to]));
        
        $settlementSteps[] = [
          'from' => $from,
          'to' => $to,
          'amount' => $amount,
        ];
        
        $balances[$from] -= $amount;
        $balances[$to] += $amount;
        $processed["$playerA-$playerB"] = true;
      }
    }
  }
  
  // Then handle remaining balances with hub approach
  $creditors = array_filter($balances, fn($b) => $b < -0.01);
  $debtors = array_filter($balances, fn($b) => $b > 0.01);
  
  foreach ($creditors as $creditorIndex => $creditorBalance) {
    foreach ($debtors as $debtorIndex => $debtorBalance) {
      if ($creditorBalance >= -0.01 || $debtorBalance <= 0.01) {
        continue;
      }
      
      $amount = min($debtorBalance, abs($creditorBalance));
      
      $settlementSteps[] = [
        'from' => $debtorIndex,
        'to' => $creditorIndex,
        'amount' => $amount,
      ];
      
      $debtors[$debtorIndex] -= $amount;
      $creditors[$creditorIndex] += $amount;
      $creditorBalance += $amount;
    }
  }
  
  return $settlementSteps;
}
