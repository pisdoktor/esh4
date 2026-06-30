            <div class="alert alert-warning py-2 small mb-3">
                <strong>adres.denizli.bel.tr</strong> üzerinden Denizli ili <strong>tüm ilçeler</strong> ve her ilçenin mahalle, sokak ve kapı numaraları çekilir.
                İşlem saatler sürebilir; tarayıcı sekmesini açık bırakın. <strong>Durdur</strong> dediğinizde ilerleme kaydedilir;
                <strong>Kaldığı yerden devam et</strong> ile aynı işten sürdürülür (TRUNCATE seçmediyseniz veritabanındaki kayıtlar korunur).
            </div>
            <div class="alert alert-info py-2 small mb-3">
                Her adımda tek ilçe / mahalle / sokak işlenir; kayıtlar <strong>upsert</strong> ile yazılır (mevcut id güncellenir, yeniler eklenir).
                Geçici API kesintilerinde ilgili sokak atlanır, senkron durmadan devam eder.
            </div>