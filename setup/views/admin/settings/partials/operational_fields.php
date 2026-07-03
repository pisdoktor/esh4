<?php

declare(strict_types=1);

/**
 * @var list<array<string, mixed>> $operationalFields
 * @var bool $operationalUseSectionPrefix Güvenlik sekmesi: bölüm önekli form adları (enabled çakışmasını önler)
 */

$operationalFields = $operationalFields ?? [];
$operationalUseSectionPrefix = !empty($operationalUseSectionPrefix);

foreach ($operationalFields as $field):
    $fKey = (string) ($field['key'] ?? '');
    $fSection = (string) ($field['section'] ?? '');
    $fType = (string) ($field['type'] ?? 'text');
    $fValue = (string) ($field['value'] ?? '');
    $fDefault = (string) ($field['default'] ?? '');
    $formKey = ($operationalUseSectionPrefix && $fSection !== '') ? $fSection . '_' . $fKey : $fKey;
    $fieldId = 'op-' . preg_replace('/[^a-z0-9_-]+/i', '-', $formKey);
    ?>
    <div class="col-12 col-md-6 col-xl-4">
        <div class="esh-settings-field mb-0">
            <?php if ($fType === 'bool'): ?>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch"
                           name="operational[<?= htmlspecialchars($formKey, ENT_QUOTES, 'UTF-8') ?>]"
                           id="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>"
                           value="1"
                           <?= $fValue === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label fw-semibold" for="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars((string) ($field['label'] ?? $fKey), ENT_QUOTES, 'UTF-8') ?>
                    </label>
                </div>
                <?php if ((string) ($field['description'] ?? '') !== ''): ?>
                    <p class="small text-muted mb-0 mt-1"><?= htmlspecialchars((string) $field['description'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            <?php else: ?>
                <label class="form-label fw-semibold" for="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars((string) ($field['label'] ?? $fKey), ENT_QUOTES, 'UTF-8') ?>
                </label>
                <?php if ((string) ($field['description'] ?? '') !== ''): ?>
                    <p class="small text-muted mb-1"><?= htmlspecialchars((string) $field['description'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <?php if ($fType === 'textarea'): ?>
                    <textarea class="form-control" id="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>"
                              name="operational[<?= htmlspecialchars($formKey, ENT_QUOTES, 'UTF-8') ?>]"
                              rows="4"><?= htmlspecialchars($fValue, ENT_QUOTES, 'UTF-8') ?></textarea>
                <?php elseif ($fType === 'enum'): ?>
                    <?php $opts = is_array($field['options'] ?? null) ? $field['options'] : []; ?>
                    <select class="form-select" id="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>"
                            name="operational[<?= htmlspecialchars($formKey, ENT_QUOTES, 'UTF-8') ?>]">
                        <?php foreach ($opts as $optVal => $optLabel): ?>
                            <option value="<?= htmlspecialchars((string) $optVal, ENT_QUOTES, 'UTF-8') ?>"<?= (string) $fValue === (string) $optVal ? ' selected' : '' ?>>
                                <?= htmlspecialchars((string) $optLabel, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="<?= $fType === 'int' || $fType === 'float' ? 'number' : 'text' ?>"
                           class="form-control"
                           id="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>"
                           name="operational[<?= htmlspecialchars($formKey, ENT_QUOTES, 'UTF-8') ?>]"
                           value="<?= htmlspecialchars($fValue, ENT_QUOTES, 'UTF-8') ?>"
                           <?php if ($fType === 'float'): ?>step="any"<?php endif; ?>
                           <?php if ($fType === 'int'): ?>step="1"<?php endif; ?>>
                <?php endif; ?>
                <div class="small text-muted mt-1">Varsayılan: <code><?= htmlspecialchars($fDefault !== '' ? $fDefault : '—', ENT_QUOTES, 'UTF-8') ?></code></div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
