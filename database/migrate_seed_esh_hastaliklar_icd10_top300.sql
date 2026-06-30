-- =============================================================================
-- İsteğe bağlı migrasyon — evde sağlık ICD-10 top 300 (eksik tanıları ekler)
-- Aynı `icd` zaten varsa atlanır (idempotent).
-- Elle: mysql -u KULLANICI -p VERITABANI < database/migrate_seed_esh_hastaliklar_icd10_top300.sql
-- veya: php tools/run_sql_migration.php database/migrate_seed_esh_hastaliklar_icd10_top300.sql
-- =============================================================================

SET NAMES utf8mb4;

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Esansiyel (primer) hipertansiyon', 'I10'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I10'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Hipertansif kalp hastalığı, kalp yetmezliği ile', 'I11.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I11.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Hipertansif kalp hastalığı, tanımlanmamış', 'I11.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I11.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Hipertansif böbrek hastalığı, böbrek yetmezliği ile', 'I12.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I12.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Hipertansif böbrek hastalığı, tanımlanmamış', 'I12.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I12.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Hipertansif kalp ve böbrek hastalığı, kalp yetmezliği ile', 'I13.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I13.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Hipertansif kalp ve böbrek hastalığı, böbrek yetmezliği ile', 'I13.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I13.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Hipertansif kalp ve böbrek hastalığı, kalp ve böbrek yetmezliği ile', 'I13.2'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I13.2'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Hipertansif kalp ve böbrek hastalığı, tanımlanmamış', 'I13.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I13.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Renovasküler hipertansiyon', 'I15.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I15.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Kararsız angina', 'I20.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I20.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Angina pektoris, tanımlanmamış', 'I20.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I20.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Akut transmural miyokard infarktüsü, ön duvar', 'I21.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I21.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Akut transmural miyokard infarktüsü, alt duvar', 'I21.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I21.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Akut transmural miyokard infarktüsü, diğer lokalizasyonlar', 'I21.2'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I21.2'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Akut subendokardiyal miyokard infarktüsü', 'I21.4'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I21.4'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Akut miyokard infarktüsü, tanımlanmamış', 'I21.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I21.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Akut iskemik kalp hastalığı, tanımlanmamış', 'I24.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I24.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Aterosklerotik kardiyovasküler hastalık', 'I25.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I25.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Eski miyokard infarktüsü', 'I25.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I25.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Eski transmural miyokard infarktüsü', 'I25.2'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I25.2'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Anevrizma, kalp duvarı', 'I25.3'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I25.3'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'İskemik kardiyomiyopati', 'I25.5'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I25.5'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Asimptomatik miyokard iskemisi', 'I25.6'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I25.6'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Kronik iskemik kalp hastalığı, tanımlanmamış', 'I25.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I25.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Akut pulmoner emboli, tanımlanmamış', 'I26.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I26.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Pulmoner kalp hastalığı, tanımlanmamış', 'I27.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I27.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Perikard hastalığı, tanımlanmamış', 'I31.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I31.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Akut ve subakut endokardit', 'I33.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I33.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Mitral (kapak) yetmezliği', 'I34.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I34.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Mitral (kapak) darlığı', 'I34.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I34.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Mitral (kapak) darlığı ve yetmezliği', 'I34.2'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I34.2'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Aort (kapak) darlığı', 'I35.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I35.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Aort (kapak) yetmezliği', 'I35.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I35.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Triküspit (kapak) darlığı', 'I36.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I36.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Triküspit (kapak) yetmezliği', 'I36.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I36.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Dilate kardiyomiyopati', 'I42.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I42.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Kardiyomiyopati, tanımlanmamış', 'I42.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I42.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Atriyoventriküler blok, birinci derece', 'I44.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I44.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Atriyoventriküler blok, ikinci derece', 'I44.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I44.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Atriyoventriküler blok, tam', 'I44.2'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I44.2'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Sol dal bloğu', 'I44.7'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I44.7'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Supraventriküler taşikardi', 'I47.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I47.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Ventriküler taşikardi', 'I47.2'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I47.2'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Atriyal fibrilasyon', 'I48.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I48.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Atriyal flutter', 'I48.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I48.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Atriyal fibrilasyon ve flutter, tanımlanmamış', 'I48.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I48.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Ventriküler fibrilasyon ve flutter', 'I49.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I49.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Diğer kardiyak aritmi, tanımlanmamış', 'I49.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I49.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Konjestif kalp yetmezliği', 'I50.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I50.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Sol ventrikül yetmezliği', 'I50.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I50.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Kronik kalp yetmezliği', 'I50.2'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I50.2'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Akut kalp yetmezliği', 'I50.3'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I50.3'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Kalp yetmezliği, tanımlanmamış', 'I50.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I50.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Kardiyomegali', 'I51.6'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I51.6'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Kalp hastalığı, tanımlanmamış', 'I51.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I51.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Subaraknoid hemoraji, tanımlanmamış', 'I60.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I60.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'İntraserebral hemoraji, tanımlanmamış', 'I61.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I61.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Serebral enfarktüs, tromboz', 'I63.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I63.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Serebral enfarktüs, emboli', 'I63.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I63.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Serebral enfarktüs, tanımlanmamış', 'I63.3'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I63.3'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Serebral enfarktüs, oklüzyon veya stenoz olmayan', 'I63.5'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I63.5'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Serebral enfarktüs, tanımlanmamış', 'I63.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I63.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'İnme, tanımlanmamış', 'I64'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I64'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Serebral ateroskleroz', 'I67.2'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I67.2'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Serebrovasküler hastalık sonrası hemipleji', 'I69.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I69.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Serebrovasküler hastalık sonrası hemiparezi', 'I69.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I69.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Serebrovasküler hastalık sonrası bilişsel işlev bozukluğu', 'I69.3'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I69.3'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Serebrovasküler hastalık sonrası afazi', 'I69.4'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I69.4'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Serebrovasküler hastalık sonrası diğer sekel', 'I69.8'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I69.8'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Serebrovasküler hastalık sonrası sekel, tanımlanmamış', 'I69.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I69.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Ateroskleroz, aorta', 'I70.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I70.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Ateroskleroz, atardamarlar', 'I70.2'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I70.2'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Ateroskleroz, diğer atardamarlar', 'I70.8'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I70.8'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Abdominal aort anevrizması, rüptür olmadan', 'I71.3'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I71.3'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Aort anevrizması, tanımlanmamış', 'I71.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I71.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Periferik vasküler hastalık, tanımlanmamış', 'I73.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I73.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Femoral ven flebiti ve tromboflebiti', 'I80.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I80.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Diğer derin periferik venlerin derin ven trombozu', 'I80.2'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I80.2'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Alt ekstremitelerin derin ven trombozu, tanımlanmamış', 'I80.3'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I80.3'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Varisli venler, ülserli alt ekstremite', 'I83.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I83.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Varisli venler, inflamasyonlu alt ekstremite', 'I83.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I83.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Varisli venler alt ekstremite, komplikasyonsuz', 'I83.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I83.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Ven yetmezliği, postflebitik', 'I87.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I87.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Basınç ülseri, venöz', 'I87.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I87.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Ven yetmezliği (kronik) (periferik)', 'I87.2'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I87.2'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Lenfödem, herhangi bir bölge', 'I89.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I89.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'İdiopatik hipotansiyon', 'I95.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I95.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 9, 'Ortostatik hipotansiyon', 'I95.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'I95.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'Motor nöron hastalığı', 'G12.2'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G12.2'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'Parkinson hastalığı', 'G20'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G20'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'İlaca bağlı parkinsonizm', 'G21.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G21.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'Alzheimer hastalığı, erken başlangıçlı', 'G30.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G30.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'Alzheimer hastalığı, geç başlangıçlı', 'G30.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G30.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'Diğer Alzheimer hastalığı', 'G30.8'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G30.8'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'Alzheimer hastalığı, tanımlanmamış', 'G30.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G30.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'Lokalize beyin atrofisi', 'G31.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G31.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'Anterior temporal lobda dejenerasyon', 'G31.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G31.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'Diğer belirtilmiş sinir sistemi dejeneratif hastalıkları', 'G31.8'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G31.8'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'Sinir sistemi dejeneratif hastalığı, tanımlanmamış', 'G31.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G31.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'Multiple skleroz', 'G35'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G35'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'Epilepsi, tanımlanmamış', 'G40.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G40.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'Migren, tanımlanmamış', 'G43.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G43.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'Geçici serebral iskemik atak, tanımlanmamış', 'G45.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G45.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'Uykusuzluk', 'G47.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G47.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'Uyku apnesi', 'G47.3'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G47.3'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'Karpal tünel sendromu', 'G56.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G56.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'Siyatik sinir lezyonları', 'G57.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G57.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'Polinöropati, tanımlanmamış', 'G62.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G62.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'Polinöropati, sistemik hastalıkta', 'G63'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G63'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'Hemipleji', 'G81.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G81.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'Hemiparezi', 'G81.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G81.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'Parapleji', 'G82.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G82.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'Paraparezi', 'G82.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G82.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'Kuadripleji', 'G82.2'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G82.2'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'Kuadriparezi', 'G82.3'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G82.3'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'Anoksik beyin hasarı', 'G93.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G93.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 6, 'Ensefalopati, tanımlanmamış', 'G93.4'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'G93.4'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 5, 'Demans, başka yerde sınıflanmamış hastalıkta', 'F03'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'F03'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 5, 'Alkol bağımlılığı sendromu', 'F10.2'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'F10.2'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 5, 'Şizofreni, tanımlanmamış', 'F20.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'F20.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 5, 'Majör depresif bozukluk, hafif epizod', 'F32.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'F32.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 5, 'Majör depresif bozukluk, orta epizod', 'F32.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'F32.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 5, 'Depresif epizod, tanımlanmamış', 'F32.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'F32.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 5, 'Rekürren depresif bozukluk, orta epizod', 'F33.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'F33.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 5, 'Panik bozukluk', 'F41.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'F41.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 5, 'Yaygın anksiyete bozukluğu', 'F41.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'F41.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 5, 'Anksiyete bozukluğu, tanımlanmamış', 'F41.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'F41.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 5, 'Posttravmatik stres bozukluğu', 'F43.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'F43.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Tip 1 diyabetes mellitus, komplikasyonsuz', 'E10.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E10.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Tip 2 diyabetes mellitus, ketoasidoz ile', 'E11.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E11.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Tip 2 diyabetes mellitus, böbrek komplikasyonları ile', 'E11.2'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E11.2'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Tip 2 diyabetes mellitus, oftalmik komplikasyonlar ile', 'E11.3'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E11.3'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Tip 2 diyabetes mellitus, nörolojik komplikasyonlar ile', 'E11.4'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E11.4'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Tip 2 diyabetes mellitus, periferik dolaşım komplikasyonları ile', 'E11.5'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E11.5'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Tip 2 diyabetes mellitus, diğer belirtilmiş komplikasyonlar ile', 'E11.6'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E11.6'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Tip 2 diyabetes mellitus, birden fazla komplikasyon ile', 'E11.7'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E11.7'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Tip 2 diyabetes mellitus, komplikasyonlarla', 'E11.8'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E11.8'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Tip 2 diyabetes mellitus, komplikasyonsuz', 'E11.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E11.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Diğer belirtilmiş diyabetes mellitus', 'E13.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E13.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Diyabetes mellitus, tanımlanmamış', 'E14.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E14.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Konjenital hipotiroidizm', 'E03.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E03.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Hipotiroidizm, tanımlanmamış', 'E03.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E03.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Orta derecede protein-enerji malnütrisyonu', 'E44.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E44.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Hafif protein-enerji malnütrisyonu', 'E44.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E44.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Protein-enerji malnütrisyonu, tanımlanmamış', 'E46'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E46'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'B grubu vitamin eksikliği, diğer', 'E53.8'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E53.8'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Vitamin D eksikliği, tanımlanmamış', 'E55.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E55.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Obezite, aşırı kalori alımı ile', 'E66.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E66.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Obezite, tanımlanmamış', 'E66.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E66.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Saf hiperkolesterolemi', 'E78.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E78.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Saf hiperglyceridemi', 'E78.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E78.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Karma hiperlipidemi', 'E78.2'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E78.2'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Diğer hiperlipidemi', 'E78.4'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E78.4'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Hiperlipidemi, tanımlanmamış', 'E78.5'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E78.5'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Bozuk kalsiyum metabolizması', 'E83.5'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E83.5'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Volüm deplesyonu', 'E86'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E86'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Hiperozmolite ve hipernatremi', 'E87.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E87.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Hiponatremi', 'E87.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E87.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Hiperkalemi', 'E87.5'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E87.5'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 4, 'Hipokalemi', 'E87.6'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'E87.6'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 10, 'Akut nazofarenjit', 'J00'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'J00'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 10, 'Akut sinüzit, tanımlanmamış', 'J01.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'J01.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 10, 'Akut farenjit, tanımlanmamış', 'J02.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'J02.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 10, 'Akut tonsillit, tanımlanmamış', 'J03.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'J03.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 10, 'Akut larenjit', 'J04.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'J04.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 10, 'Akut üst solunum yolu enfeksiyonu, tanımlanmamış', 'J06.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'J06.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 10, 'Pnömoni, Streptococcus pneumoniae', 'J13'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'J13'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 10, 'Bakteriyel pnömoni, tanımlanmamış', 'J15.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'J15.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 10, 'Lob pnömoni, tanımlanmamış', 'J18.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'J18.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 10, 'Pnömoni, tanımlanmamış', 'J18.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'J18.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 10, 'Akut bronşit, tanımlanmamış', 'J20.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'J20.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 10, 'Akut bronşiolit, tanımlanmamış', 'J21.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'J21.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 10, 'Akut alt solunum yolu enfeksiyonu, tanımlanmamış', 'J22'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'J22'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 10, 'Bronşit, tanımlanmamış', 'J40'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'J40'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 10, 'Basit kronik bronşit', 'J41.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'J41.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 10, 'Kronik bronşit, tanımlanmamış', 'J42'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'J42'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 10, 'Amfizem, tanımlanmamış', 'J43.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'J43.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 10, 'KOAH, akut alt solunum yolu enfeksiyonu ile', 'J44.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'J44.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 10, 'KOAH, akut alevlenme ile', 'J44.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'J44.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 10, 'Kronik obstrüktif akciğer hastalığı, tanımlanmamış', 'J44.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'J44.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 10, 'Astım, tanımlanmamış', 'J45.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'J45.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 10, 'Bronşektazi', 'J47.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'J47.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 10, 'Aspirasyon pnömoni', 'J69.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'J69.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 10, 'İnterstisyel pulmoner hastalık, tanımlanmamış', 'J84.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'J84.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 10, 'Plevral efüzyon, açıklanmamış', 'J90'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'J90'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 10, 'Akut solunum yetmezliği', 'J96.00'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'J96.00'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 10, 'Kronik solunum yetmezliği', 'J96.10'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'J96.10'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 14, 'Akut böbrek yetmezliği, tanımlanmamış', 'N17.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'N17.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 14, 'Kronik böbrek hastalığı, evre 1', 'N18.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'N18.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 14, 'Kronik böbrek hastalığı, evre 2', 'N18.2'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'N18.2'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 14, 'Kronik böbrek hastalığı, evre 3', 'N18.3'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'N18.3'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 14, 'Kronik böbrek hastalığı, evre 4', 'N18.4'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'N18.4'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 14, 'Kronik böbrek hastalığı, evre 5', 'N18.5'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'N18.5'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 14, 'Son dönem böbrek hastalığı', 'N18.6'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'N18.6'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 14, 'Kronik böbrek hastalığı, tanımlanmamış', 'N18.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'N18.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 14, 'Böbrek taşı', 'N20.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'N20.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 14, 'Üreter taşı', 'N20.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'N20.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 14, 'Bozulmuş renal-tübüler fonksiyon, tanımlanmamış', 'N25.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'N25.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 14, 'Akut sistit', 'N30.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'N30.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 14, 'İnterstisyel sistit (kronik)', 'N30.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'N30.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 14, 'Nörojen mesane, tanımlanmamış', 'N31.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'N31.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 14, 'Üretra striktürü, tanımlanmamış', 'N35.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'N35.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 14, 'Üriner sistem hastalığı, tanımlanmamış', 'N36.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'N36.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 14, 'Üriner sistem enfeksiyonu, yeri tanımlanmamış', 'N39.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'N39.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 14, 'Hiperplazi, prostat', 'N40'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'N40'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 14, 'Sistosel', 'N81.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'N81.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 14, 'Rektosel', 'N81.3'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'N81.3'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 14, 'Perineal prolapsus', 'N81.4'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'N81.4'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 14, 'Pelvik organ prolapsusu, tanımlanmamış', 'N81.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'N81.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 14, 'Obstrüktif ve reflü üropati, tanımlanmamış', 'N13.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'N13.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 13, 'Primer koksartroz, bilateral', 'M16.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'M16.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 13, 'Koksartroz, tanımlanmamış', 'M16.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'M16.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 13, 'Primer gonartroz, bilateral', 'M17.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'M17.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 13, 'Gonartroz, tanımlanmamış', 'M17.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'M17.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 13, 'Artroz, tanımlanmamış', 'M19.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'M19.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 13, 'Spondiloz, tanımlanmamış', 'M47.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'M47.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 13, 'Spinal stenoz', 'M48.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'M48.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 13, 'Lomber disk bozukluğu, radikülopati ile', 'M51.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'M51.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 13, 'Servikalgiya', 'M54.2'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'M54.2'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 13, 'Lombaağrı', 'M54.5'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'M54.5'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 13, 'Dorsalgiya, tanımlanmamış', 'M54.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'M54.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 13, 'Kas zayıflığı (genelize)', 'M62.81'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'M62.81'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 13, 'Miyalji', 'M79.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'M79.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 13, 'Siyatika', 'M79.3'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'M79.3'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 13, 'Postmenopozal osteoporoz, patolojik kırık ile', 'M80.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'M80.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 13, 'Postmenopozal osteoporoz', 'M81.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'M81.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 13, 'Osteoporoz, tanımlanmamış', 'M81.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'M81.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 19, 'Torakal vertebra kırığı', 'S22.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'S22.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 19, 'Lumbar vertebra kırığı', 'S32.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'S32.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 19, 'Klavikula kırığı', 'S42.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'S42.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 19, 'Radius alt uç kırığı', 'S52.5'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'S52.5'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 19, 'Femur boyun kırığı', 'S72.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'S72.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 19, 'Pertrokanterik kırık', 'S72.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'S72.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 19, 'Ayak bileği burkulması ve distorsiyonu', 'S93.4'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'S93.4'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 11, 'Gastro-özofageal reflü hastalığı, özofajit ile', 'K21.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'K21.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 11, 'Gastro-özofageal reflü hastalığı, özofajit olmaksızın', 'K21.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'K21.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 11, 'Gastrik ülser, tanımlanmamış', 'K25.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'K25.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 11, 'Duodenal ülser, tanımlanmamış', 'K26.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'K26.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 11, 'Peptik ülser, yeri tanımlanmamış', 'K27.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'K27.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 11, 'Gastrit ve duodenit, tanımlanmamış', 'K29.5'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'K29.5'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 11, 'Fonksiyonel dispepsi', 'K30'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'K30'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 11, 'Gastroenterit ve kolit, enfektif olmayan', 'K52.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'K52.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 11, 'Diğer ve tanımlanmamış intestinal tıkanıklık', 'K56.6'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'K56.6'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 11, 'Diyvertiküler hastalık, perforasyon veya apse olmadan', 'K57.3'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'K57.3'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 11, 'Kabızlık', 'K59.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'K59.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 11, 'Hipermotilite sendromu', 'K59.2'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'K59.2'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 11, 'Alkolik karaciğer hastalığı, tanımlanmamış', 'K70.30'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'K70.30'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 11, 'Siroz ve karaciğer fibrozisi, tanımlanmamış', 'K74.6'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'K74.6'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 11, 'Gastrointestinal hemoraji, tanımlanmamış', 'K92.2'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'K92.2'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 2, 'Maligan neoplazm, kolon, tanımlanmamış', 'C18.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'C18.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 2, 'Maligan neoplazm, rektum', 'C20'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'C20'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 2, 'Maligan neoplazm, pankreas, tanımlanmamış', 'C25.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'C25.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 2, 'Maligan neoplazm, bronş veya akciğer, tanımlanmamış', 'C34.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'C34.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 2, 'Maligan neoplazm, meme, tanımlanmamış', 'C50.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'C50.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 2, 'Maligan neoplazm, prostat', 'C61'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'C61'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 2, 'Maligan neoplazm, mesane, tanımlanmamış', 'C67.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'C67.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 2, 'Maligan neoplazm, beyin, tanımlanmamış', 'C71.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'C71.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 2, 'İkincil malign neoplazm, yeri belirtilmemiş', 'C79.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'C79.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 2, 'Maligan neoplazm, birincil yeri bilinmiyor', 'C80.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'C80.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 21, 'Genel tıbbi muayene', 'Z00.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'Z00.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 21, 'Trizom bakımı', 'Z43.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'Z43.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 21, 'Kolostomi bakımı', 'Z43.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'Z43.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 21, 'İleostomi bakımı', 'Z43.3'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'Z43.3'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 21, 'Üriner kateter bakımı', 'Z46.6'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'Z46.6'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 21, 'Diğer belirtilmiş cihazların uygulanması ve bakımı', 'Z46.8'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'Z46.8'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 21, 'Palyatif bakım', 'Z51.5'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'Z51.5'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 21, 'Aortokoroner baypas greft varlığı', 'Z95.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'Z95.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 21, 'Kalp kapak protezi ve greft varlığı', 'Z95.5'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'Z95.5'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 21, 'Kalp kapak protezi varlığı', 'Z96.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'Z96.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 21, 'Bağımlılık, invaziv mekanik ventilatör', 'Z99.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'Z99.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 21, 'Bağımlılık, kalp-akciğer makinesi', 'Z99.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'Z99.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 21, 'Bağımlılık, renal diyaliz', 'Z99.2'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'Z99.2'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 21, 'Bağımlılık, kronik oksijen tedavisi', 'Z99.3'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'Z99.3'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 21, 'Bağımlılık, diğer destekleyici önlemler', 'Z99.8'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'Z99.8'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 1, 'Diyare ve gastroenterit, enfeksiyöz etken varsayımı', 'A09'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'A09'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 1, 'Sepsis, tanımlanmamış', 'A41.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'A41.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 1, 'Bakteriyel enfeksiyon, tanımlanmamış', 'A49.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'A49.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 1, 'Zona, komplikasyonsuz', 'B02.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'B02.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 1, 'Kandidiyazis, tanımlanmamış', 'B37.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'B37.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 12, 'Selülit, tanımlanmamış', 'L03.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'L03.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 12, 'Dermatit, tanımlanmamış', 'L30.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'L30.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 12, 'Basınç ülseri, evre I', 'L89.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'L89.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 12, 'Basınç ülseri, evre II', 'L89.1'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'L89.1'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 12, 'Basınç ülseri, evre III', 'L89.2'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'L89.2'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 12, 'Basınç ülseri, evre IV', 'L89.3'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'L89.3'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 12, 'Basınç ülseri, tanımlanmamış', 'L89.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'L89.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 12, 'Ayak ülseri, diyabetes mellitus dışı', 'L97'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'L97'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 18, 'Öksürük', 'R05'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'R05'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 18, 'Dispne', 'R06.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'R06.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 18, 'Diğer ve tanımlanmamış karın ağrısı', 'R10.4'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'R10.4'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 18, 'Bulantı ve kusma', 'R11'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'R11'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 18, 'Hasta yürüyemiyor', 'R26.2'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'R26.2'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 18, 'Ateş, tanımlanmamış', 'R50.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'R50.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 18, 'Ağrı, tanımlanmamış', 'R52.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'R52.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 18, 'Senkop ve kollaps', 'R55'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'R55'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 19, 'İç eklem protezi mekanik komplikasyonu', 'T84.0'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'T84.0'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 3, 'Demir eksikliği anemisi, tanımlanmamış', 'D50.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'D50.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 3, 'Vitamin B12 eksikliği anemisi, tanımlanmamış', 'D51.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'D51.9'
);

INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)
SELECT 0, 3, 'Anemi, tanımlanmamış', 'D64.9'
WHERE NOT EXISTS (
  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = 'D64.9'
);

