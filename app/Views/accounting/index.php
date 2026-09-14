<div class="page-head"><div><h2>Accounting</h2><p>Cashbook, AR, and a simple P&amp;L from the ledger.</p></div></div>
<div class="grid grid-4">
    <div class="card stat"><div class="label">Cash in</div><div class="value"><?= e(money($cash['cash_in'] ?? 0)) ?></div></div>
    <div class="card stat"><div class="label">Cash out</div><div class="value"><?= e(money($cash['cash_out'] ?? 0)) ?></div></div>
    <div class="card stat"><div class="label">Accounts receivable</div><div class="value"><?= e(money($ar['ar'] ?? 0)) ?></div></div>
    <div class="card stat"><div class="label">P&amp;L</div><div class="value"><?= e(money(($income['income'] ?? 0) - ($expense['expense'] ?? 0))) ?></div></div>
</div>
<div class="card" style="margin-top:16px;">
    <div class="card-head">Recent ledger entries</div>
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>Date</th><th>Account</th><th>Description</th><th>Debit</th><th>Credit</th></tr></thead>
            <tbody>
            <?php foreach ($entries as $entry): ?>
                <tr>
                    <td><?= e(format_date($entry['entry_date'])) ?></td>
                    <td><?= e($entry['code']) ?> <?= e($entry['account_name']) ?></td>
                    <td><?= e($entry['description']) ?></td>
                    <td><?= e(money($entry['debit'])) ?></td>
                    <td><?= e(money($entry['credit'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php \App\Core\View::include('partials.pagination', ['page' => $page, 'pages' => $pages]); ?>
</div>
