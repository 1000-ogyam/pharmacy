<?php
$page = max(1, (int) ($page ?? 1));
$pages = max(1, (int) ($pages ?? 1));
$base = $base ?? request_path();
$pageParam = $pageParam ?? 'page';
$query = is_array($query ?? null) ? $query : $_GET;
unset($query[$pageParam]);
if ($pages > 1):
?>
<div class="pagination">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
        <?php
            $params = $query;
            $params[$pageParam] = $i;
            $href = url($base) . '?' . http_build_query($params);
        ?>
        <?php if ($i === $page): ?>
            <span><?= $i ?></span>
        <?php else: ?>
            <a href="<?= e($href) ?>"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>
</div>
<?php endif; ?>
