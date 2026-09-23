<?php
/** @var string $type */
/** @var array<string, array{label: string, class: class-string}> $types */
/** @var bool $canManage */
?>
<div class="page-head">
    <div>
        <h2>Archives</h2>
        <p>Deleted records kept for review. Administrators can restore or permanently remove them.</p>
    </div>
    <a class="btn btn-outline" href="<?= e(url('/sales')) ?>">Sales history</a>
</div>
<div class="card"><div class="card-body">
    <div class="help-jump" style="margin-bottom:12px;">
        <?php foreach ($types as $key => $meta): ?>
            <a class="btn btn-sm <?= $key === $type ? '' : 'btn-outline' ?>" href="<?= e(url('/archives?type=' . $key)) ?>"><?= e($meta['label']) ?></a>
        <?php endforeach; ?>
    </div>
    <form class="searchbar" method="get" action="<?= e(url('/archives')) ?>" data-live-search data-live-search-target="[data-live-results]">
        <input type="hidden" name="type" value="<?= e($type) ?>">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search…" autocomplete="off">
    </form>
</div></div>
<div class="card" style="margin-top:16px;" data-live-results><div class="table-wrap">
    <table class="data">
        <thead>
            <tr>
                <th>Record</th>
                <th>Details</th>
                <?php if ($type === 'sales' || $type === 'purchase-orders' || $type === 'quotations'): ?><th>Amount</th><?php endif; ?>
                <th>Archived</th>
                <?php if ($canManage): ?><th></th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e((string) ($row['label'] ?? $row['id'])) ?></td>
                <td><?= e((string) ($row['extra'] ?? $row['status'] ?? '—')) ?></td>
                <?php if ($type === 'sales' || $type === 'purchase-orders' || $type === 'quotations'): ?>
                    <td><?= isset($row['total']) ? e(money($row['total'])) : '—' ?></td>
                <?php endif; ?>
                <td><?= e(format_datetime($row['deleted_at'] ?? '')) ?></td>
                <?php if ($canManage): ?>
                <td class="table-actions">
                    <form method="post" action="<?= e(url('/archives/' . $type . '/' . $row['id'] . '/restore')) ?>" style="display:inline;">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline btn-sm">Restore</button>
                    </form>
                    <button type="button" class="btn btn-danger btn-sm"
                        data-modal-confirm="Permanently delete this record? This cannot be undone."
                        data-modal-title="Permanent delete"
                        data-action="<?= e(url('/archives/' . $type . '/' . $row['id'])) ?>"
                        data-method="DELETE">Delete forever</button>
                </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
            <tr><td colspan="<?= $canManage ? ($type === 'sales' || $type === 'purchase-orders' ? 5 : 4) : ($type === 'sales' || $type === 'purchase-orders' ? 4 : 3) ?>">No archived <?= e(strtolower($types[$type]['label'])) ?>.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
    $query = ['type' => $type];
    if ($q !== '') {
        $query['q'] = $q;
    }
    \App\Core\View::include('partials.pagination', ['page' => $page, 'pages' => $pages, 'base' => '/archives', 'query' => $query]);
?>
</div>
