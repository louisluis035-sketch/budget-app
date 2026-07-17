<?php
// api.php — Sehemu zote za mantiki zilizokuwa kwenye localStorage sasa
// zimehamishiwa hapa. Kila ombi (request) huzungumza na MySQL kupitia PDO.

declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
// bootstrap.php tayari imefungua $pdo (MySQL, kwa kutumia config.php yako
// ya InfinityFree), imeanzisha session, na imeweka Content-Type header.

$rawBody = file_get_contents('php://input');
$input = json_decode($rawBody ?: '{}', true);
if (!is_array($input)) {
    $input = [];
}
$action = $input['action'] ?? ($_GET['action'] ?? '');

function respond(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function requireAuth(): int
{
    if (empty($_SESSION['user_id'])) {
        respond(['error' => 'Huja bado kuingia.'], 401);
    }
    return (int) $_SESSION['user_id'];
}

function fetchUserRow(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function userPublic(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'username' => $row['username'],
        'jina' => $row['jina'],
        'salioAwali' => (float) $row['salio_awali'],
        'bajetiMwezi' => (float) $row['bajeti_mwezi'],
        'bajetiAllocated' => (float) $row['bajeti_allocated'],
    ];
}

function fetchTransactions(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('SELECT * FROM transactions WHERE user_id = ? ORDER BY `date` DESC, created_at DESC');
    $stmt->execute([$userId]);
    return array_map(function ($r) {
        return [
            'id' => $r['id'],
            'type' => $r['type'],
            'amount' => (float) $r['amount'],
            'category' => $r['category'],
            'note' => $r['note'],
            'date' => $r['date'],
            'createdAt' => (int) $r['created_at'],
            'updatedAt' => $r['updated_at'] !== null ? (int) $r['updated_at'] : null,
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function fetchDeleted(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('SELECT * FROM deleted_transactions WHERE user_id = ? ORDER BY deleted_at DESC');
    $stmt->execute([$userId]);
    return array_map(function ($r) {
        return [
            'id' => $r['id'],
            'type' => $r['type'],
            'amount' => (float) $r['amount'],
            'category' => $r['category'],
            'note' => $r['note'],
            'date' => $r['date'],
            'deletedAt' => (int) $r['deleted_at'],
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function monthBounds(): array
{
    return [date('Y-m-01'), date('Y-m-t')];
}

function inMonthWindow(string $date): bool
{
    [$start, $end] = monthBounds();
    return $date >= $start && $date <= $end;
}

function monthKeyOf(string $date): string
{
    return substr($date, 0, 7);
}

function monthLabelSw(string $monthKeyStr): string
{
    $months = ['Januari', 'Februari', 'Machi', 'Aprili', 'Mei', 'Juni', 'Julai', 'Agosti', 'Septemba', 'Oktoba', 'Novemba', 'Desemba'];
    [$y, $m] = explode('-', $monthKeyStr);
    return $months[(int) $m - 1] . ' ' . $y;
}

function computeStats(array $userRow, array $transactions): array
{
    $today = date('Y-m-d');
    $curMonth = monthKeyOf($today);
    $expenseMonth = 0.0;
    $expenseToday = 0.0;
    $lossMonth = 0.0;
    $incomeMonth = 0.0;

    foreach ($transactions as $t) {
        $inWindow = inMonthWindow($t['date']);
        if ($t['type'] === 'mapato' && $inWindow) $incomeMonth += $t['amount'];
        if ($t['type'] === 'matumizi' && $inWindow) $expenseMonth += $t['amount'];
        if ($t['type'] === 'matumizi' && $t['date'] === $today) $expenseToday += $t['amount'];
        if ($t['type'] === 'hasara' && $inWindow) $lossMonth += $t['amount'];
    }

    $allocated = (float) ($userRow['bajeti_allocated'] ?: $userRow['bajeti_mwezi']);
    $budgetUsed = min($allocated, max(0.0, $expenseMonth));
    $budgetRemaining = max(0.0, $allocated - $budgetUsed);
    $outOfBudgetExpense = max(0.0, $expenseMonth - $budgetUsed);
    $balance = (float) $userRow['salio_awali'] + $incomeMonth - $lossMonth - $outOfBudgetExpense;

    $months = [];
    foreach ($transactions as $t) {
        $mk = monthKeyOf($t['date']);
        if ($mk === $curMonth) continue;
        if (!isset($months[$mk])) $months[$mk] = ['income' => 0.0, 'expense' => 0.0, 'loss' => 0.0];
        if ($t['type'] === 'mapato') $months[$mk]['income'] += $t['amount'];
        if ($t['type'] === 'matumizi') $months[$mk]['expense'] += $t['amount'];
        if ($t['type'] === 'hasara') $months[$mk]['loss'] += $t['amount'];
    }
    krsort($months);
    $monthlyRecords = [];
    foreach ($months as $mk => $vals) {
        $monthlyRecords[] = [
            'month' => $mk,
            'label' => monthLabelSw($mk),
            'income' => $vals['income'],
            'expense' => $vals['expense'],
            'loss' => $vals['loss'],
        ];
    }

    return [
        'balance' => $balance,
        'expenseToday' => $expenseToday,
        'expenseMonth' => $expenseMonth,
        'lossMonth' => $lossMonth,
        'budgetUsed' => $budgetUsed,
        'budgetRemaining' => $budgetRemaining,
        'monthlyRecords' => $monthlyRecords,
        'currentMonthLabel' => monthLabelSw($curMonth),
        'allocatedBudget' => $allocated,
    ];
}

function fullState(PDO $pdo, int $userId): array
{
    $userRow = fetchUserRow($pdo, $userId);
    if (!$userRow) {
        respond(['error' => 'Mtumiaji hajapatikana.'], 404);
    }
    $transactions = fetchTransactions($pdo, $userId);
    $deleted = fetchDeleted($pdo, $userId);
    $stats = computeStats($userRow, $transactions);
    return [
        'user' => userPublic($userRow),
        'transactions' => $transactions,
        'deletedTransactions' => $deleted,
        'stats' => $stats,
    ];
}

function canModify(array $tx): bool
{
    $createdAt = (int) ($tx['createdAt'] ?? 0);
    if (!$createdAt) return true;
    return (time() * 1000 - $createdAt) <= 2 * 60 * 60 * 1000;
}

function makeId(): string
{
    return bin2hex(random_bytes(5));
}

switch ($action) {

    case 'register': {
        $jina = trim((string) ($input['jina'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $password = (string) ($input['password'] ?? '');
        if ($jina === '' || $email === '' || $password === '') {
            respond(['error' => 'Tafadhali jaza sehemu zote.'], 400);
        }
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            respond(['error' => 'Barua pepe tayari imetumika.'], 409);
        }
        $salioAwali = (float) ($input['salioAwali'] ?? 0);
        $bajeti = (float) ($input['bajeti'] ?? 0);
        $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, jina, salio_awali, bajeti_mwezi, bajeti_allocated) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT), $jina, $salioAwali, $bajeti, $bajeti]);
        $_SESSION['user_id'] = (int) $pdo->lastInsertId();
        respond(fullState($pdo, $_SESSION['user_id']));
    }

    case 'login': {
        $identifier = trim((string) ($input['identifier'] ?? ''));
        $password = (string) ($input['password'] ?? '');
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$identifier]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || !password_verify($password, $row['password_hash'])) {
            respond(['error' => 'Barua pepe/jina la mtumiaji au nenosiri si sahihi.'], 401);
        }
        $_SESSION['user_id'] = (int) $row['id'];
        respond(fullState($pdo, $_SESSION['user_id']));
    }

    case 'logout': {
        $_SESSION = [];
        session_destroy();
        respond(['ok' => true]);
    }

    case 'state': {
        $userId = requireAuth();
        respond(fullState($pdo, $userId));
    }

    case 'save_transaction': {
        $userId = requireAuth();
        $type = (string) ($input['type'] ?? '');
        $amount = (float) ($input['amount'] ?? 0);
        $category = $type === 'matumizi' ? (string) ($input['category'] ?? '') : null;
        $note = (string) ($input['note'] ?? '');
        $date = (string) ($input['date'] ?? date('Y-m-d'));
        $id = $input['id'] ?? null;

        if (!in_array($type, ['mapato', 'matumizi', 'hasara'], true) || $amount <= 0) {
            respond(['error' => 'Data ya muamala si sahihi.'], 400);
        }

        $userRow = fetchUserRow($pdo, $userId);
        $existing = null;
        if ($id) {
            $stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = ? AND user_id = ?');
            $stmt->execute([$id, $userId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$existing) {
                respond(['error' => 'Muamala haujapatikana.'], 404);
            }
            $existingForCheck = [
                'id' => $existing['id'], 'type' => $existing['type'], 'amount' => (float) $existing['amount'],
                'date' => $existing['date'], 'createdAt' => (int) $existing['created_at'],
            ];
            if (!canModify($existingForCheck)) {
                respond(['error' => 'Muamala huu hauwezi kuhaririwa baada ya masaa 2.'], 403);
            }
        }

        // Simulate the balance after this change, mirroring the original client-side check:
        // an expense/loss cannot push the overall balance negative.
        if (in_array($type, ['matumizi', 'hasara'], true)) {
            $transactions = fetchTransactions($pdo, $userId);
            if ($existing) {
                $transactions = array_values(array_filter($transactions, fn($t) => $t['id'] !== $existing['id']));
            }
            $transactions[] = ['type' => $type, 'amount' => $amount, 'date' => $date];
            $projected = computeStats($userRow, $transactions);
            if ($projected['balance'] < 0) {
                $current = computeStats($userRow, fetchTransactions($pdo, $userId));
                respond(['error' => 'Salio haijatosha kwa muamala huu. Salio lako sasa: ' . number_format($current['balance']) . '.'], 422);
            }
        }

        $now = (int) (microtime(true) * 1000);
        if ($existing) {
            $stmt = $pdo->prepare('UPDATE transactions SET type=?, amount=?, category=?, note=?, `date`=?, updated_at=? WHERE id=? AND user_id=?');
            $stmt->execute([$type, $amount, $category, $note, $date, $now, $id, $userId]);
        } else {
            $newId = makeId();
            $stmt = $pdo->prepare('INSERT INTO transactions (id, user_id, type, amount, category, note, `date`, created_at) VALUES (?,?,?,?,?,?,?,?)');
            $stmt->execute([$newId, $userId, $type, $amount, $category, $note, $date, $now]);
        }
        respond(fullState($pdo, $userId));
    }

    case 'delete_transaction': {
        $userId = requireAuth();
        $id = (string) ($input['id'] ?? '');
        $stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        $tx = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$tx) {
            respond(['error' => 'Muamala haujapatikana.'], 404);
        }
        $txForCheck = ['createdAt' => (int) $tx['created_at']];
        if (!canModify($txForCheck)) {
            respond(['error' => 'Muamala huu hauwezi kufutwa baada ya masaa 2.'], 403);
        }
        $now = (int) (microtime(true) * 1000);
        $ins = $pdo->prepare('INSERT INTO deleted_transactions (id, user_id, type, amount, category, note, `date`, created_at, deleted_at) VALUES (?,?,?,?,?,?,?,?,?)');
        $ins->execute([$tx['id'], $userId, $tx['type'], $tx['amount'], $tx['category'], $tx['note'], $tx['date'], $tx['created_at'], $now]);
        $del = $pdo->prepare('DELETE FROM transactions WHERE id = ? AND user_id = ?');
        $del->execute([$id, $userId]);
        respond(fullState($pdo, $userId));
    }

    case 'update_profile': {
        $userId = requireAuth();
        $jina = trim((string) ($input['jina'] ?? ''));
        $budget = (float) ($input['bajeti'] ?? 0);
        $userRow = fetchUserRow($pdo, $userId);
        $currentAllocated = (float) ($userRow['bajeti_allocated'] ?: $userRow['bajeti_mwezi']);
        $currentStats = computeStats($userRow, fetchTransactions($pdo, $userId));
        $availableMain = $currentStats['balance'] + $currentAllocated;
        if ($budget > $availableMain) {
            respond(['error' => 'Bajeti isiyofaa. Salio lako linaloopatikana ni ' . number_format($availableMain) . ', lakini unataka kuweka bajeti ya ' . number_format($budget) . '.'], 422);
        }
        $delta = $budget - $currentAllocated;
        $newSalioAwali = (float) $userRow['salio_awali'] - $delta;
        $stmt = $pdo->prepare('UPDATE users SET jina=?, bajeti_mwezi=?, bajeti_allocated=?, salio_awali=? WHERE id=?');
        $stmt->execute([$jina !== '' ? $jina : $userRow['jina'], $budget, $budget, $newSalioAwali, $userId]);
        respond(fullState($pdo, $userId));
    }

    case 'set_budget': {
        $userId = requireAuth();
        $transferAmount = (float) ($input['amount'] ?? 0);
        if ($transferAmount <= 0) {
            respond(['error' => 'Tafadhali ingiza kiasi halali cha kuhamisha kwenye bajeti.'], 400);
        }
        $userRow = fetchUserRow($pdo, $userId);
        $currentStats = computeStats($userRow, fetchTransactions($pdo, $userId));
        if ($transferAmount > $currentStats['balance']) {
            respond(['error' => 'Salio lako halitoshi. Salio lako sasa ni ' . number_format($currentStats['balance']) . ', lakini unataka kuhamisha ' . number_format($transferAmount) . '.'], 422);
        }
        $currentAllocated = (float) ($userRow['bajeti_allocated'] ?: $userRow['bajeti_mwezi']);
        $newAllocated = $currentAllocated + $transferAmount;
        $newSalioAwali = (float) $userRow['salio_awali'] - $transferAmount;
        $stmt = $pdo->prepare('UPDATE users SET bajeti_allocated=?, bajeti_mwezi=?, salio_awali=? WHERE id=?');
        $stmt->execute([$newAllocated, $newAllocated, $newSalioAwali, $userId]);
        respond(fullState($pdo, $userId));
    }

    default:
        respond(['error' => 'Kitendo hakijulikani.'], 400);
}
