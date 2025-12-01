<?php require 'logic.php'; ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Voor alleen de mannen 🚗💥🔫</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <h1>Voor alleen de mannen 🚗💥🔫</h1>

    <?php if (getTheSumOfTheGame() !== 0) : ?>
        <p class="warning">Er is een fout in het spel, de som van de betalingen klopt niet! (<?php echo number_format(getTheSumOfTheGame() / 100, 2, ',', '.'); ?>)</p>
    <?php endif; ?>

    <div class="share-panel">
        <div class="share-panel__text">
            <strong>Deel of bewaar spel</strong>
            <p>Sla de huidige stand op als link. Iedereen met de link kan het spel exact zo terugzetten.</p>
        </div>
        <form action="" method="post" class="share-panel__form">
            <input type="hidden" name="action" value="share_state">
            <button type="submit">Maak share link</button>
        </form>
    </div>

    <?php if (!empty($shareNotice)) : ?>
        <p class="share-feedback success"><?php echo htmlspecialchars($shareNotice); ?></p>
    <?php endif; ?>

    <?php if (!empty($shareError)) : ?>
        <p class="share-feedback error"><?php echo htmlspecialchars($shareError); ?></p>
    <?php endif; ?>

    <?php if (!empty($shareUrl)) : ?>
        <div class="share-result">
            <label for="share-url">Deel-link (zet spel terug naar deze stand):</label>
            <div class="share-url-row">
                <input id="share-url" type="text" value="<?php echo htmlspecialchars($shareUrl); ?>" readonly>
                <button type="button" class="copy-button" data-copy-target="share-url">Kopieer</button>
            </div>
        </div>
    <?php endif; ?>

    <div class="balance-legend">
        <div class="legend-copy">
            <strong>Hoe lees je de saldo's?</strong>
            <p>Positief = te betalen (heeft meer ontvangen dan ingelegd). Negatief = te ontvangen (heeft meer ingelegd dan ontvangen). De bank beweegt mee als speler.</p>
        </div>
        <div class="legend-pills">
            <span class="tone-pill debt">Te betalen</span>
            <span class="tone-pill credit">Te ontvangen</span>
            <span class="tone-pill even">In evenwicht</span>
        </div>
    </div>

    <?php if (isset($_SESSION['players']) && is_array($_SESSION['players']) && count($_SESSION['players']) > 1) : ?>
        <fieldset class="payments-fieldset">
            <legend>Betalingen</legend>
            <?php if (isset($_SESSION['payments']) && is_array($_SESSION['payments']) && count($_SESSION['payments']) > 0) : ?>
                <?php $balanceTimeline = getBalanceTimeline(); ?>
                <div class="impact-table-wrapper">
                    <table class="impact-table payments-table">
                        <thead>
                            <tr>
                                <th>Beschrijving</th>
                                <?php foreach ($_SESSION['players'] as $balancePlayerIndex => $playerData) : ?>
                                    <th>
                                        <?php echo htmlspecialchars($playerData['name']); ?>
                                        <?php if ($balancePlayerIndex === 0) : ?><span class="tag bank-tag">Bank</span><?php endif; ?>
                                    </th>
                                <?php endforeach; ?>
                                <th>&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($_SESSION['payments'] as $paymentIndex => $paymentData) : ?>
                                <?php
                                $beforeBalances = $balanceTimeline[$paymentIndex]['before'] ?? [];
                                $afterBalances = $balanceTimeline[$paymentIndex]['after'] ?? $beforeBalances;
                                $description = htmlspecialchars($_SESSION['players'][$paymentData['player']]['name'])
                                    . ' geeft '
                                    . formatEuro($paymentData['amount'])
                                    . ' aan '
                                    . htmlspecialchars($_SESSION['players'][$paymentData['receiver']]['name']);
                                ?>
                                <tr>
                                    <td data-label="Beschrijving"><?php echo $description; ?></td>
                                    <?php foreach ($_SESSION['players'] as $balancePlayerIndex => $playerData) :
                                        $before = $beforeBalances[$balancePlayerIndex] ?? 0;
                                        $after = $afterBalances[$balancePlayerIndex] ?? $before;
                                        $delta = $after - $before;
                                        $deltaClass = $delta > 0 ? 'positive' : ($delta < 0 ? 'negative' : 'even');
                                    ?>
                                        <td data-label="<?php echo htmlspecialchars($playerData['name']); ?>">
                                            <div class="impact-balance"><?php echo formatEuro($after, true); ?></div>
                                            <div class="impact-delta <?php echo $deltaClass; ?>">
                                                <?php echo $delta === 0 ? 'geen wijziging' : formatEuro($delta, true); ?>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                    <td data-label="Verwijderen">
                                        <form action="" method="post">
                                            <input type="hidden" name="action" value="delete_payment">
                                            <input type="hidden" name="payment" value="<?php echo htmlspecialchars($paymentIndex) ?>">
                                            <input type="submit" value="x">
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <hr>
            <?php endif ?>
            <form action="" method="post">
                <input type="hidden" name="action" value="payment">
                <label for="player">Speler:</label>
                <select name="player" id="player">
                    <?php foreach ($_SESSION['players'] as $playerIndex => $playerData) : ?>
                        <option value="<?php echo htmlspecialchars($playerIndex) ?>"><?php echo htmlspecialchars($playerData['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                betaald
                <input type="number" name="amount" step="0.01" min="0" value="0" required>
                aan
                <select name="receiver" id="receiver">
                    <?php foreach ($_SESSION['players'] as $playerIndex => $playerData) : ?>
                        <option value="<?php echo htmlspecialchars($playerIndex) ?>"><?php echo htmlspecialchars($playerData['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" value="Toevoegen">
            </form>
        </fieldset>
    <?php endif ?>

    <div class="fieldset-container">
        <fieldset>
            <legend>Spelers</legend>
            <?php if (isset($_SESSION['players']) && is_array($_SESSION['players']) && count($_SESSION['players']) > 0) : ?>
                <table>
                    <thead>
                        <tr>
                            <th>Naam</th>
                            <th>Saldo</th>
                            <th>Status</th>
                            <th>&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['players'] as $playerIndex => $playerData) : ?>
                            <?php $balance = getBalanceOfPlayer($playerIndex);
                            $balanceMeta = getBalanceMeta($balance); ?>
                            <tr>
                                <td data-label="Naam">
                                    <?php echo htmlspecialchars($playerData['name']); ?>
                                    <?php if ($playerIndex === 0) : ?><span class="tag bank-tag">Bank</span><?php endif; ?>
                                </td>
                                <td data-label="Saldo"><?php echo formatEuro($balance, true); ?></td>
                                <td data-label="Status">
                                    <span class="tone-pill <?php echo $balanceMeta['tone']; ?>"><?php echo $balanceMeta['label']; ?></span>
                                </td>
                                <td>
                                    <?php if ($playerIndex === 0) : ?>
                                        &nbsp;
                                    <?php else : ?>
                                        <form action="" method="post">
                                            <input type="hidden" name="action" value="delete_player">
                                            <input type="hidden" name="player" value="<?php echo htmlspecialchars($playerIndex) ?>">
                                            <input type="submit" value="x">
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <hr>
            <?php endif ?>

            <form action="" method="post">
                <label for="name">Naam:</label>
                <input type="text" id="name" name="name">
                <input type="hidden" name="action" value="add_player">
                <input type="submit" value="Toevoegen">
            </form>
        </fieldset>

        <?php if (!isset($_SESSION['payments']) || (isset($_SESSION['payments']) && is_array($_SESSION['payments']) && count($_SESSION['payments']) === 0)) : ?>
            <fieldset>
                <legend>Spel starten</legend>
                <form action="" method="post">
                    <label for="amount">Bedrag voor de buy-in:</label>
                    <input type="number" id="amount" name="amount" step="0.01" min="0" value="20" required>
                    <input type="hidden" name="action" value="start_game">
                    <input type="submit" value="Start spel">
                </form>
            </fieldset>
<?php endif ?>

    </div>

<?php if (needToSettle()) : ?>
    <fieldset>
        <legend>Onderling te verrekenen</legend>
        <form action="" method="post" style="margin-bottom: 1em;">
            <input type="hidden" name="action" value="set_settlement_strategy">
            <label for="strategy">Afreken methode:</label>
            <select name="strategy" id="strategy" onchange="this.form.submit()">
                <option value="hub" <?php echo ($_SESSION['settlement_strategy'] ?? 'hub') === 'hub' ? 'selected' : ''; ?>>Hub-gebaseerd (aanbevolen)</option>
                <option value="direct" <?php echo ($_SESSION['settlement_strategy'] ?? 'hub') === 'direct' ? 'selected' : ''; ?>>Direct (natuurlijker)</option>
                <option value="minimal" <?php echo ($_SESSION['settlement_strategy'] ?? 'hub') === 'minimal' ? 'selected' : ''; ?>>Minimaal (minste transacties)</option>
            </select>
            <noscript><input type="submit" value="Toepassen"></noscript>
        </form>
        <?php $settlementSteps = getSettlementSteps(); ?>
        <?php if (count($settlementSteps) > 0) : ?>
            <table>
                <thead>
                    <tr>
                        <th>Van</th>
                        <th>Bedrag</th>
                        <th>Aan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($settlementSteps as $settlementStep) : ?>
                        <tr>
                            <td data-label="Van"><?php echo htmlspecialchars($_SESSION['players'][$settlementStep['from']]['name']); ?></td>
                            <td data-label="Bedrag">€ <?php echo number_format($settlementStep['amount'] / 100, 2, ',', '.'); ?></td>
                            <td data-label="Naar"><?php echo htmlspecialchars($_SESSION['players'][$settlementStep['to']]['name']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif ?>
    </fieldset>
<?php endif ?>
<form action="" method="post">
    <input type="hidden" name="action" value="reset">
    <input class="reset" type="submit" value="Begin opnieuw">
</form>
<script>
    (function() {
        var copyButtons = document.querySelectorAll('[data-copy-target]');
        copyButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                var targetId = button.getAttribute('data-copy-target');
                var input = document.getElementById(targetId);
                if (!input) {
                    return;
                }

                input.select();
                input.setSelectionRange(0, input.value.length);
                var text = input.value;
                var originalLabel = button.textContent;

                var markCopied = function() {
                    button.textContent = 'Gekopieerd!';
                    setTimeout(function() {
                        button.textContent = originalLabel;
                    }, 1200);
                };

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(markCopied).catch(function() {
                        document.execCommand('copy');
                        markCopied();
                    });
                } else {
                    document.execCommand('copy');
                    markCopied();
                }
            });
        });
    })();
</script>
</body>

</html>
