create database mybank;
use mybank;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matricule VARCHAR(50) UNIQUE NOT NULL,
    nom VARCHAR(100),
    prenom VARCHAR(100),
    role ENUM('caissier', 'charge_clientele', 'responsable') NOT NULL,
    mdp VARCHAR(255) NOT NULL
);


CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telephone VARCHAR(20) NOT NULL,
    adresse TEXT NOT NULL,
    date_naissance DATE NOT NULL,
    cin VARCHAR(20) NOT NULL UNIQUE,
    date_inscription DATETIME NOT NULL
);



CREATE TABLE comptes_bancaires (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_compte VARCHAR(20) UNIQUE NOT NULL,
    client_id INT NOT NULL,
    type_compte ENUM('courant', 'epargne') NOT NULL,
    solde DECIMAL(10,2) DEFAULT 0,
    code_secret VARCHAR(255) NOT NULL, 
    date_creation DATE,
    signature_image VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (client_id) REFERENCES clients(id)
);


CREATE TABLE cartes_bancaires (
    id INT PRIMARY KEY AUTO_INCREMENT,
    compte_id INT,
    numero_carte VARCHAR(16) UNIQUE,
    date_expiration DATE,
    cvv VARCHAR(4),
    type ENUM('Débit', 'Crédit'),
    FOREIGN KEY (compte_id) REFERENCES comptes_bancaires(id)
);


CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    compte_source_id INT,
    compte_dest_id INT,
    montant DECIMAL(10,2),
    date_transaction DATETIME DEFAULT CURRENT_TIMESTAMP,
    type ENUM('virement', 'retrait', 'depot'),
    FOREIGN KEY (compte_source_id) REFERENCES comptes_bancaires(id),
    FOREIGN KEY (compte_dest_id) REFERENCES comptes_bancaires(id)
);



CREATE TABLE reclamations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    date_reclamation DATETIME DEFAULT CURRENT_TIMESTAMP,
    objet VARCHAR(255),
    message TEXT,
    statut ENUM('en_attente', 'en_cours', 'traitee') DEFAULT 'en_attente',
    traite_par INT,
    FOREIGN KEY (user_id) REFERENCES clients(id),
    FOREIGN KEY (traite_par) REFERENCES users(id)
);



CREATE TABLE chequiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    type_chequier ENUM('nouveau', 'reedition') NOT NULL,
    date_demande DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);



CREATE TABLE credits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    montant DECIMAL(10, 2) NOT NULL,
    motif TEXT NOT NULL,
    date_demande DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);