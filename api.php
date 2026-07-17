<?php
// api.php — Mfumo wa Wallet Halisi (Live Balances) wenye MySQL na PDO.
// Toleo lililorekebishwa: Kuhamisha bajeti sasa kunaongeza dari la mwezi kiotomatiki.

declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

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
        'salioKuu' => (float) $row['salio_awali'], 
        'bajetiMwezi' => (float) $row['bajeti_mwezi'],
        'bajetiIliyobaki' => (float) $row['bajeti_allocated'], 
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
            'createdAt' => strtotime($r['created_at']) * 1000,
            'updatedAt' => $r['updated_at'] !== null ? strtotime($r['updated_at']) * 1000 : null,
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
            'deletedAt' => strtotime($r['deleted_at']) * 1000,
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

    $balance = (float) $userRow['salio_awali'];
    $budgetRemaining = (float) $userRow['bajeti_allocated'];
    $allocatedBudget = (float) $userRow['bajeti_mwezi'];

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
        'budgetUsed' => max(0.0, $allocatedBudget - $budgetRemaining), 
        'budgetRemaining' => $budgetRemaining,     
        'monthlyRecords' => $monthlyRecords,
        'currentMonthLabel' => monthLabelSw($curMonth),
        'allocatedBudget' => $allocatedBudget,
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
        
        if ($bajeti < 0 || $salioAwali < 0) {
            respond(['error' => 'Kiasi cha salio au bajeti hakiwezi kuwa chini ya sifuri.'], 422);
        }
        if ($bajeti > $salioAwali) {
            respond(['error' => 'Huwezi kuweka bajeti kubwa kuliko salio lako la mwanzo.'], 422);
        }
        
        $salioSasa = $salioAwali - $bajeti;

        $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, jina, salio_awali, bajeti_mwezi, bajeti_allocated) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT), $jina, $salioSasa, $bajeti, $bajeti]);
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
        $liveBalance = (float) $userRow['salio_awali'];
        $liveBudget = (float) $userRow['bajeti_allocated'];
        $targetBudget = (float) $userRow['bajeti_mwezi'];

        $existing = null;
        if ($id) {
            $stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = ? AND user_id = ?');
            $stmt->execute([$id, $userId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$existing) {
                respond(['error' => 'Muamala haujapatikana.'], 404);
            }
            $existingForCheck = [
                'id' => $existing['id'],
                'type' => $existing['type'],
                'amount' => (float) $existing['amount'],
                'date' => $existing['date'],
                'createdAt' => strtotime($existing['created_at']) * 1000,
            ];
            if (!canModify($existingForCheck)) {
                respond(['error' => 'Muamala huu hauwezi kuhaririwa baada ya masaa 2.'], 403);
            }
        }

        if ($existing) {
            $oldType = $existing['type'];
            $oldAmount = (float) $existing['amount'];

            $tempBalance = $liveBalance;
            $tempBudget = $liveBudget;

            if ($oldType === 'mapato') $tempBalance -= $oldAmount;
            if ($oldType === 'hasara') $tempBalance += $oldAmount;
            if ($oldType === 'matumizi') $tempBudget += $oldAmount;

            if ($type === 'mapato') $tempBalance += $amount;
            if ($type === 'hasara') $tempBalance -= $amount;
            if ($type === 'matumizi') $tempBudget -= $amount;

            if ($tempBalance < 0) {
                respond(['error' => 'Mabadiliko yamekataliwa! Salio lako kuu halitoshi.'], 422);
            }
            if ($type === 'matumizi' && $tempBudget < 0) {
                respond(['error' => 'Mabadiliko yamekataliwa! Bajeti yako haitatosha. Inabaki: ' . number_format($liveBudget + $oldAmount)], 422);
            }
            
            if ($tempBudget > $targetBudget) {
                $tempBudget = $targetBudget;
            }

            $liveBalance = $tempBalance;
            $liveBudget = $tempBudget;
        } else {
            if ($type === 'matumizi') {
                if ($liveBudget <= 0) {
                    respond([
                        'error' => 'Bajeti yako imeisha. Tafadhali ongeza bajeti kwanza kabla ya kurekodi matumizi mengine.'
                    ], 422);
                }

                if ($amount > $liveBudget) {
                    respond([
                        'error' => 'Muamala umekataliwa! Bajeti iliyobaki ni TSh ' . number_format($liveBudget) . ' tu.'
                    ], 422);
                }
            }

            if ($type === 'hasara' && $amount > $liveBalance) {
                respond(['error' => 'Muamala umekataliwa! Salio kuu halitoshi kufidia hasara hii.'], 422);
            }

            if ($type === 'mapato') {
                $liveBalance += $amount;
            }
            if ($type === 'hasara') {
                $liveBalance -= $amount;
            }
            if ($type === 'matumizi') {
                $liveBudget -= $amount; 
            }
        }

        $now = date('Y-m-d H:i:s');
        if ($existing) {
            $stmt = $pdo->prepare('UPDATE transactions SET type=?, amount=?, category=?, note=?, `date`=?, updated_at=? WHERE id=? AND user_id=?');
            $stmt->execute([$type, $amount, $category, $note, $date, $now, $id, $userId]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO transactions (user_id, type, amount, category, note, `date`, created_at) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute([$userId, $type, $amount, $category, $note, $date, $now]);
        }

        $stmt = $pdo->prepare('UPDATE users SET salio_awali = ?, bajeti_allocated = ? WHERE id = ?');
        $stmt->execute([$liveBalance, $liveBudget, $userId]);

        respond(fullState($pdo, $userId));
    }

    case 'delete_transaction': {
        $userId = requireAuth();
        $id = $input['id'] ?? '';
        $stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        $tx = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$tx) {
            respond(['error' => 'Muamala haujapatikana.'], 404);
        }
        $txForCheck = ['createdAt' => strtotime($tx['created_at']) * 1000];
        if (!canModify($txForCheck)) {
            respond(['error' => 'Muamala huu hauwezi kufutwa baada ya masaa 2.'], 403);
        }

        $userRow = fetchUserRow($pdo, $userId);
        $liveBalance = (float) $userRow['salio_awali'];
        $liveBudget = (float) $userRow['bajeti_allocated'];
        $amount = (float) $tx['amount'];

        if ($tx['type'] === 'mapato' && ($liveBalance - $amount) < 0) {
            respond(['error' => 'Imeshindikana kufuta muamala huu! Kufuta hapa kutapeleka Salio Kuu chini ya sifuri.'], 422);
        }

        if ($tx['type'] === 'mapato') $liveBalance -= $amount; 
        if ($tx['type'] === 'hasara') $liveBalance += $amount; 
        
        if ($tx['type'] === 'matumizi') {
            $liveBudget = min($liveBudget + $amount, (float) $userRow['bajeti_mwezi']);
        }

        $now = date('Y-m-d H:i:s');
        $ins = $pdo->prepare('INSERT INTO deleted_transactions (id, user_id, type, amount, category, note, `date`, created_at, deleted_at) VALUES (?,?,?,?,?,?,?,?,?)');
        $ins->execute([$tx['id'], $userId, $tx['type'], $tx['amount'], $tx['category'], $tx['note'], $tx['date'], $tx['created_at'], $now]);
        
        $del = $pdo->prepare('DELETE FROM transactions WHERE id = ? AND user_id = ?');
        $del->execute([$id, $userId]);

        $stmt = $pdo->prepare('UPDATE users SET salio_awali = ?, bajeti_allocated = ? WHERE id = ?');
        $stmt->execute([$liveBalance, $liveBudget, $userId]);

        respond(fullState($pdo, $userId));
    }

    case 'update_profile': {
        $userId = requireAuth();
        $jina = trim((string) ($input['jina'] ?? ''));
        $userRow = fetchUserRow($pdo, $userId);
        
        $liveBalance = (float) $userRow['salio_awali'];
        $oldBudgetSetting = (float) $userRow['bajeti_mwezi'];
        $newBudgetSetting = isset($input['bajeti']) ? (float) $input['bajeti'] : $oldBudgetSetting;
        
        if ($newBudgetSetting < 0) {
            respond(['error' => 'Bajeti haiwezi kuwa chini ya sifuri.'], 422);
        }

        $used = $oldBudgetSetting - (float) $userRow['bajeti_allocated'];

        if ($newBudgetSetting < $used) {
            respond([
                'error' => 'Huwezi kuweka bajeti chini ya kiasi ambacho tayari kimetumika (Kimetumika: ' . number_format($used) . ').'
            ], 422);
        }

        $newRemaining = $newBudgetSetting - $used;
        $delta = $newBudgetSetting - $oldBudgetSetting;

        if ($delta > 0 && $delta > $liveBalance) {
            respond(['error' => 'Imeshindikana! Salio lako kuu halitoshi kufidia nyongeza ya bajeti kuu.'], 422);
        }

        $liveBalance -= $delta;
        
        $stmt = $pdo->prepare('UPDATE users SET jina=?, bajeti_mwezi=?, bajeti_allocated=?, salio_awali=? WHERE id=?');
        $stmt->execute([
            $jina !== '' ? $jina : $userRow['jina'], 
            $newBudgetSetting, 
            $newRemaining, 
            $liveBalance, 
            $userId
        ]);
        respond(fullState($pdo, $userId));
    }

    case 'set_budget': {
        $userId = requireAuth();
        $transferAmount = (float) ($input['amount'] ?? 0);
        if ($transferAmount <= 0) {
            respond(['error' => 'Tafadhali ingiza kiasi halali cha kuhamisha kwenye bajeti.'], 400);
        }
        $userRow = fetchUserRow($pdo, $userId);
        $liveBalance = (float) $userRow['salio_awali'];
        $liveBudget = (float) $userRow['bajeti_allocated'];
        $targetBudget = (float) $userRow['bajeti_mwezi'];

        if ($liveBalance <= 0 || $transferAmount > $liveBalance) {
            respond(['error' => 'Imeshindikana! Salio lako kuu la sasa (' . number_format($liveBalance) . ') halitoshi kuhamisha ' . number_format($transferAmount) . ' kwenda kwenye bajeti.'], 422);
        }
        
        // REKEBISHO: Pesa ikihamishwa, inaongeza bajeti iliyobaki na dari kuu la mwezi kwa pamoja!
        $newBudgetAllocated = $liveBudget + $transferAmount;
        $newBudgetMwezi = $targetBudget + $transferAmount; 
        $newBalance = $liveBalance - $transferAmount;
        
        $stmt = $pdo->prepare('UPDATE users SET bajeti_allocated=?, bajeti_mwezi=?, salio_awali=? WHERE id=?');
        $stmt->execute([$newBudgetAllocated, $newBudgetMwezi, $newBalance, $userId]);
        respond(fullState($pdo, $userId));
    }

    default:
        respond(['error' => 'Kitendo hakijulikani.'], 400);
}
