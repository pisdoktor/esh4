-- Stok takip modülü RBAC izinleri

INSERT IGNORE INTO `esh_permissions` (`module_key`, `crud`, `slug`, `label`) VALUES
('stok', 'read', 'stok.read', 'Stok takibi — okuma'),
('stok', 'create', 'stok.create', 'Stok takibi — çıkış / iade'),
('stok', 'admin', 'stok.admin', 'Stok takibi — malzeme kartı ve giriş');
