# Evde Sağlık ICD-10 Top 300



Evde sağlık (ESH) hasta profiline uygun **300 ICD-10-TR tanısı**; `esh_hastaliklar` tablosu için isteğe bağlı migration dosyası üretir.



## Dosyalar



| Dosya | Açıklama |

|-------|----------|

| `inc_evde_saglik_icd10_candidates.php` | 300 tanı adayı (`icd`, `hastalikadi`) |

| `generate_evde_saglik_icd10_top300_sql.php` | Migration SQL üretici |

| `../database/seed/seed_esh_hastaliklar.sql` | Kurulum seed (~14.7k ICD-10-TR tanısı) |

| `../database/migrate_seed_esh_hastaliklar_icd10_top300.sql` | Mevcut DB için idempotent ekleme (hızlı paket) |

| `../database/import/README_icd10.txt` | SKRS ile katalog güncelleme |



## Kurulum (yeni sistem)



`Installer` kurulum sırasında `database/seed/seed_esh_hastalikcat.sql` (21 kategori + `icd_range`) ve `database/seed/seed_esh_hastaliklar.sql` (tam platform kataloğu) dosyalarını otomatik içe aktarır.



**SKRS ile katalog güncelleme** (kurulum sonrası, isteğe bağlı):



```bash

php tools/build_icd10_hastaliklar_from_skrs.php

php tools/migrate_import_icd10_hastaliklar.php

```



## Hızlı başlangıç (~300 evde sağlık tanısı)



Kurulum seed’i zaten tam kataloğu içerir. Eski veya eksik veritabanları için:



Aynı `icd` kodu zaten varsa satır atlanır (idempotent):



```bash

php tools/run_sql_migration.php database/migrate_seed_esh_hastaliklar_icd10_top300.sql

```



## Kategori eşlemesi



`cat` alanı ICD-10 harfine göre `esh_hastalikcat` (id **1–21**) ile eşlenir (`App\Helpers\Icd10CatMapper`):



- A–B → 1, C → 2, D50–D89 → 3, diğer D → 2

- E → 4, F → 5, G → 6

- H00–H59 → 7, H60–H95 → 8

- I → 9, J → 10, K → 11, L → 12, M → 13, N → 14

- O → 15, P → 16, Q → 17, R → 18, S–T → 19, V–Y → 20, Z → 21

- U04/U07 → 10 (solunum), diğer U → 1



`kurum_id = 0` — platform geneli katalog (branş ve işlem seed’leri ile uyumlu).



## Veri kaynakları



- Klimik Dergisi 2024 — evde sağlık enfeksiyonları; HT, DM, demans, KOAH, KAH, inme, kalp yetmezliği sıklığı

- Adana ŞEH ESH bir yıllık kayıt (TJFMPC) — HT, SVO, DM; ağrı, yatak yarası, ÜSYE

- Evde sağlık hasta karakteristikleri çalışmaları — Alzheimer/demans, kalp hastalığı, pansuman ihtiyacı

- Birinci basamak TR reçete verisi 2013–2016 (Mollahaliloğlu vd.) — I10, J06.9, J03, K21, M79.1, Z00.0



## Dağılım (yaklaşık)



| ICD bölümü | Oran | Örnek tanılar |

|------------|------|----------------|

| I Dolaşım | ~32% | I10, I50, I63, I48, I25 |

| G–F Sinir / psikiyatrik | ~14% | G30, G20, G81, F03, F32 |

| E Endokrin | ~11% | E11, E03, E78 |

| J Solunum | ~9% | J44, J18, J06.9 |

| N Genitoüriner | ~7% | N18, N39.0 |

| M–S Kas-iskelet | ~7% | M79.1, S72.0, M54.5 |

| K Sindirim | ~5% | K21 |

| C Onkoloji | ~4% | C34, C61, Z51.5 |

| Z Sağlık faktörleri | ~5% | Z99, Z43, Z46.6 |

| Diğer (A, B, L, R, T, D) | ~6% | L89, R52.9, A41.9 |



## Örnek doğrulama



Üretici çalıştırıldığında örnek eşlemeler kontrol edilir: I10→9, G30.9→6, J44.9→10, Z51.5→21.

