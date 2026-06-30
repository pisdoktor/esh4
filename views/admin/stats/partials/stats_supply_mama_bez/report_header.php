<h4 class="fw-bold mb-2">Mama ve bez rapor bitiş tarihleri</h4>
    <?php require dirname(__DIR__, 4) . '/partials/admin/stats_page_intro.php'; ?>
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'mama' ? 'active' : '' ?>" href="<?= htmlspecialchars(esh_url('Stats', 'supplyReports', ['tab' => 'mama', 'date_from' => $date_from, 'date_to' => $date_to]), ENT_QUOTES, 'UTF-8') ?>">Mama</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'bez' ? 'active' : '' ?>" href="<?= htmlspecialchars(esh_url('Stats', 'supplyReports', ['tab' => 'bez', 'date_from' => $date_from, 'date_to' => $date_to]), ENT_QUOTES, 'UTF-8') ?>">Bez</a>
        </li>
    </ul>