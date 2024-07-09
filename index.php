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

    <div class="fieldset-container">
        <fieldset>
            <legend>Spelers</legend>
            <?php if (isset($_SESSION['players']) && is_array($_SESSION['players']) && count($_SESSION['players']) > 0) : ?>
                <table>
                    <thead>
                        <tr>
                            <th>Naam</th>
                            <th>Saldo</th>
                            <th>&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['players'] as $playerIndex => $playerData) : ?>
                            <tr>
                                <td data-label="Naam"><?php echo htmlspecialchars($playerData['name']); ?></td>
                                <td data-label="Saldo">€ <?php echo number_format(getBalanceOfPlayer($playerIndex) / 100, 2, ',', '.') ?></td>
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
    </div>
<?php endif ?>

<?php if (isset($_SESSION['players']) && is_array($_SESSION['players']) && count($_SESSION['players']) > 1) : ?>
    <fieldset>
        <legend>Betalingen</legend>
        <?php if (isset($_SESSION['payments']) && is_array($_SESSION['payments']) && count($_SESSION['payments']) > 0) : ?>
            <table>
                <thead>
                    <tr>
                        <th>Speler</th>
                        <th>Bedrag</th>
                        <th>Ontvanger</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['payments'] as $paymentIndex => $paymentData) : ?>
                        <tr>
                            <td data-label="Speler"><?php echo htmlspecialchars($_SESSION['players'][$paymentData['player']]['name']) ?></td>
                            <td data-label="Bedrag">€ <?php echo number_format($paymentData['amount'] / 100, 2, ',', '.') ?></td>
                            <td data-label="Ontvanger"><?php echo htmlspecialchars($_SESSION['players'][$paymentData['receiver']]['name']) ?></td>
                            <td>
                                <form action="" method="post">
                                    <input type="hidden" name="action" value="delete_payment">
                                    <input type="hidden" name="payment" value="<?php echo htmlspecialchars($paymentIndex) ?>">
                                    <input type="submit" value="x">
                                </form>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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

<?php if (needToSettle()) : ?>
    <fieldset>
        <legend>Onderling te verrekenen</legend>
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
</div>

<form action="" method="post">
    <input type="hidden" name="action" value="reset">
    <input class="reset" type="submit" value="Begin opnieuw">
</form>
</body>

</html>