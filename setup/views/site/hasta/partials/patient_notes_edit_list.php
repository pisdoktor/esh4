<?php
/**
 * Hasta düzenleme — kayıtlı notlar listesi (JSON `notes` alanı).
 *
 * @var \App\Models\Patient $patient
 */
$eshPatient = $patient ?? null;
if (!$eshPatient || !isset($eshPatient->id)) {
    return;
}

$allNotes = json_decode((string) ($eshPatient->notes ?? ''), true);
if (!is_array($allNotes)) {
    $allNotes = [];
}

$eshHasNotes = false;
foreach (array_reverse($allNotes, true) as $index => $noteRaw) {
    if (!is_array($noteRaw)) {
        $noteRaw = ['message' => (string) $noteRaw];
    }
    $noteDate = trim((string) ($noteRaw['date'] ?? $noteRaw['tarih'] ?? ''));
    $noteMessage = trim((string) ($noteRaw['message'] ?? $noteRaw['text'] ?? $noteRaw['not'] ?? $noteRaw['content'] ?? ''));
    if ($noteDate === '' && $noteMessage === '') {
        continue;
    }
    $eshHasNotes = true;
    $noteDateEsc = htmlspecialchars($noteDate !== '' ? $noteDate : '—', ENT_QUOTES, 'UTF-8');
    $noteMessageEsc = htmlspecialchars($noteMessage, ENT_QUOTES, 'UTF-8');
    ?>
    <div class="p-2 mb-2 bg-light rounded border position-relative note-item">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="badge bg-white text-dark border fw-normal shadow-xs" style="font-size: 0.75rem;">
                <i class="fa-solid fa-calendar-day me-1 text-warning" aria-hidden="true"></i> <?= $noteDateEsc ?>
            </span>
            <button type="button" class="btn btn-link text-danger p-0 m-0"
                    onclick="deleteNote(this, <?= (int) $eshPatient->id ?>, <?= (int) $index ?>)"
                    aria-label="Notu sil">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>
        <p class="small mb-0 text-dark"><?= nl2br($noteMessageEsc) ?></p>
    </div>
    <?php
}

if (!$eshHasNotes) {
    ?>
    <div class="text-center py-3 border rounded bg-light">
        <em class="small text-muted">Henüz kayıtlı bir not bulunmuyor.</em>
    </div>
    <?php
}
