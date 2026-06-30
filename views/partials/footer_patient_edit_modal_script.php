<?php
declare(strict_types=1);
if (!empty($GLOBALS['eshPatientViewEditModals'])) {
    echo '<script src="' . htmlspecialchars(ASSETS_URL . '/pages/js/patient-edit.js', ENT_QUOTES, 'UTF-8') . '"></script>' . "\n";
}
