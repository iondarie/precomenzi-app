DROP DATABASE IF EXISTS precomenzi_app;
CREATE DATABASE precomenzi_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE precomenzi_app;

CREATE TABLE utilizatori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nume VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    parola_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'agent') NOT NULL,
    data_creare TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE clienti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nume_client VARCHAR(150) NOT NULL,
    cod_fiscal VARCHAR(50) NOT NULL,
    adresa TEXT NOT NULL,
    agent_id INT NOT NULL,
    data_creare TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_clienti_agent FOREIGN KEY (agent_id) REFERENCES utilizatori(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE produse (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cod_produs VARCHAR(100) NOT NULL UNIQUE,
    denumire VARCHAR(200) NOT NULL,
    multiplu_cantitate INT NOT NULL DEFAULT 1,
    activ BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE comenzi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    agent_id INT NOT NULL,
    data_comanda DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    saptamana_an VARCHAR(20) NOT NULL,
    status ENUM('in_lucru', 'finalizata') NOT NULL DEFAULT 'in_lucru',
    CONSTRAINT fk_comenzi_client FOREIGN KEY (client_id) REFERENCES clienti(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_comenzi_agent FOREIGN KEY (agent_id) REFERENCES utilizatori(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE comanda_detalii (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comanda_id INT NOT NULL,
    produs_id INT NOT NULL,
    cantitate INT NOT NULL,
    CONSTRAINT fk_detalii_comanda FOREIGN KEY (comanda_id) REFERENCES comenzi(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_detalii_produs FOREIGN KEY (produs_id) REFERENCES produse(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_cantitate_positive CHECK (cantitate > 0)
);

INSERT INTO utilizatori (nume, email, parola_hash, rol) VALUES
('Administrator', 'admin@precomenzi.ro', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Agent 1', 'agent1@precomenzi.ro', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'agent'),
('Agent 2', 'agent2@precomenzi.ro', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'agent');

INSERT INTO clienti (nume_client, cod_fiscal, adresa, agent_id) VALUES
('SC Alfa SRL', 'RO12345678', 'Str. Primaverii 12, Cluj-Napoca', 2),
('Farmacia Verde', 'RO87654321', 'Bulevardul Traian 45, Sibiu', 2),
('Magazinul 24', 'RO65432198', 'Piața Centrală 7, Timișoara', 3),
('Lumea Cărnii', 'RO45678912', 'Str. Moldova 9, Iași', 3);

INSERT INTO produse (cod_produs, denumire, multiplu_cantitate, activ) VALUES
('PRD-001', 'Pâine albă', 6, TRUE),
('PRD-002', 'Pâine integrală', 12, TRUE),
('PRD-003', 'Lapte 1L', 6, TRUE),
('PRD-004', 'Ouă 10 buc', 12, TRUE),
('PRD-005', 'Brânză de burduf', 6, TRUE),
('PRD-006', 'Biscuiți naturali', 12, TRUE);

INSERT INTO comenzi (client_id, agent_id, data_comanda, saptamana_an, status) VALUES
((SELECT id FROM clienti WHERE nume_client = 'SC Alfa SRL' LIMIT 1), 2, NOW(), DATE_FORMAT(CURDATE(), '%x-W%v'), 'in_lucru'),
((SELECT id FROM clienti WHERE nume_client = 'Magazinul 24' LIMIT 1), 3, NOW(), DATE_FORMAT(CURDATE(), '%x-W%v'), 'finalizata'),
((SELECT id FROM clienti WHERE nume_client = 'Farmacia Verde' LIMIT 1), 2, NOW(), DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 WEEK), '%x-W%v'), 'finalizata');

INSERT INTO comanda_detalii (comanda_id, produs_id, cantitate) VALUES
((SELECT c.id FROM comenzi c JOIN clienti cl ON cl.id = c.client_id WHERE cl.nume_client = 'SC Alfa SRL' ORDER BY c.id DESC LIMIT 1), (SELECT id FROM produse WHERE cod_produs = 'PRD-001'), 12),
((SELECT c.id FROM comenzi c JOIN clienti cl ON cl.id = c.client_id WHERE cl.nume_client = 'SC Alfa SRL' ORDER BY c.id DESC LIMIT 1), (SELECT id FROM produse WHERE cod_produs = 'PRD-003'), 18),
((SELECT c.id FROM comenzi c JOIN clienti cl ON cl.id = c.client_id WHERE cl.nume_client = 'Magazinul 24' ORDER BY c.id DESC LIMIT 1), (SELECT id FROM produse WHERE cod_produs = 'PRD-002'), 24),
((SELECT c.id FROM comenzi c JOIN clienti cl ON cl.id = c.client_id WHERE cl.nume_client = 'Magazinul 24' ORDER BY c.id DESC LIMIT 1), (SELECT id FROM produse WHERE cod_produs = 'PRD-004'), 12),
((SELECT c.id FROM comenzi c JOIN clienti cl ON cl.id = c.client_id WHERE cl.nume_client = 'Farmacia Verde' ORDER BY c.id DESC LIMIT 1), (SELECT id FROM produse WHERE cod_produs = 'PRD-005'), 18),
((SELECT c.id FROM comenzi c JOIN clienti cl ON cl.id = c.client_id WHERE cl.nume_client = 'Farmacia Verde' ORDER BY c.id DESC LIMIT 1), (SELECT id FROM produse WHERE cod_produs = 'PRD-006'), 12);

CREATE INDEX idx_clienti_agent ON clienti(agent_id);
CREATE INDEX idx_comenzi_agent_week ON comenzi(agent_id, saptamana_an);
CREATE INDEX idx_comenzi_client ON comenzi(client_id);
CREATE INDEX idx_detalii_comanda ON comanda_detalii(comanda_id);
CREATE INDEX idx_detalii_produs ON comanda_detalii(produs_id);
