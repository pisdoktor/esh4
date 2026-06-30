            <div class="small text-muted mt-3" id="statusLine"><?= htmlspecialchars($statusMessage, ENT_QUOTES, 'UTF-8') ?></div>
            <p class="small fw-semibold text-muted mb-2 mt-3">İşlem günlüğü</p>
            <div class="border rounded bg-dark text-light p-2 small ir-migration-log" id="logBox">
                <div class="text-muted opacity-75">Scrape başlayınca adımlar burada listelenir.</div>
            </div>
            <style>
                .ir-migration-log {
                    min-height: 200px;
                    max-height: 320px;
                    overflow: auto;
                    font-family: Consolas, Monaco, 'Courier New', monospace;
                    font-size: 12px;
                    line-height: 1.5;
                    letter-spacing: 0;
                }
                .ir-migration-log-line {
                    display: block;
                    margin: 0;
                    padding: 1px 0;
                    white-space: pre-wrap;
                    overflow-wrap: anywhere;
                    word-break: break-word;
                }
            </style>