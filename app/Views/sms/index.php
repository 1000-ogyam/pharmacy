<div class="page-head">
    <div>
        <h2>SMS</h2>
        <p>Messages go to the queue, then out through Arkesel. Run cron or send the queue from here.</p>
    </div>
    <div class="table-actions">
        <form method="post" action="<?= e(url('/sms/process')) ?>">
            <?= csrf_field() ?>
            <button class="btn btn-outline" type="submit">Send queued messages</button>
        </form>
        <button type="button" class="btn" data-modal-src="#sms-form" data-modal-title="Queue SMS">Queue SMS</button>
    </div>
</div>
<div class="card" style="margin-bottom:16px;">
    <div class="card-body">
        Gateway: <strong><?= e(ucfirst((string) ($gateway ?? 'arkesel'))) ?></strong>
        · Sender ID: <strong><?= e((string) ($senderId ?? 'PLPharma')) ?></strong>
        ·
        <?php if (!empty($apiConfigured)): ?>
            <span class="badge badge-success">API key set</span>
        <?php else: ?>
            <span class="badge badge-amber">No API key — sends are simulated</span>
        <?php endif; ?>
        <?php if (!empty($sandbox)): ?>
            <span class="badge badge-navy">Sandbox</span>
        <?php endif; ?>
    </div>
</div>
<template id="sms-form">
    <form method="post" action="<?= e(url('/sms')) ?>">
        <?= csrf_field() ?>
        <div class="form-row"><label>To</label><input name="to" required placeholder="0244… or 23324…"></div>
        <div class="form-row" style="margin-top:8px;"><label>Message</label><textarea name="message" rows="4" required></textarea></div>
        <button class="btn" style="margin-top:12px;">Queue SMS</button>
    </form>
</template>
<div class="grid grid-2">
    <div class="card"><div class="card-body">
        <h3 style="margin:0 0 12px;">Templates</h3>
        <ul>
            <?php foreach ($templates as $template): ?>
                <li><strong><?= e($template->key) ?></strong> — <?= e($template->body) ?></li>
            <?php endforeach; ?>
        </ul>
    </div></div>
    <div class="card"><div class="table-wrap">
        <table class="data">
            <thead><tr><th>To</th><th>Status</th><th>Message</th></tr></thead>
            <tbody>
            <?php foreach ($queue as $row): ?>
                <tr>
                    <td><?= e($row['to_number']) ?></td>
                    <td><span class="badge badge-navy"><?= e($row['status']) ?></span></td>
                    <td><?= e(mb_strimwidth((string) $row['message'], 0, 60, '…')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php \App\Core\View::include('partials.pagination', ['page' => $page, 'pages' => $pages]); ?>
    </div>
</div>
