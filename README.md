# Precomenzi App

Aplicatie PHP + MySQL pentru gestionarea precomenzilor saptamanale.

## Functionalitati principale
- autentificare administratori si agenti
- reguli de acces (ACL)
- gestionare produse in back-office
- introducere precomanda pentru agenti
- validare cantitate multiplu
- fereastra de comanda in intervalul Miercuri 00:00 - Marți 12:00
- export PDF centralizat

## Structura proiectului
- `schema.sql` - schema bazei de date si seed data
- `config.php` - configurare aplicatie
- `includes/db.php` - conexiune PDO si utilitati
- `includes/auth.php` - sesiuni, CSRF si ACL
- `public/login.php` - pagina de autentificare
- `public/admin/index.php` - back-office administrare
- `public/agent/index.php` - formular precomanda agent
- `public/pdf/report.php` - generare PDF
- `public/assets/styles.css` - stiluri custom
- `public/assets/app.js` - validare JS pentru multiplu

## Instalare
1. Creeaza baza de date si ruleaza `schema.sql`.
2. Configureaza `config.php` sau `.env` cu datele MySQL.
3. Ruleaza `composer install`.
4. Porneste un server PHP local:
   `php -S localhost:8000 -t public`
5. Acceseaza `http://localhost:8000/login.php`.

## Conturi demo
- admin@precomenzi.ro / password
- agent1@precomenzi.ro / password
- agent2@precomenzi.ro / password

## Observatii
- SQL este securizat cu PDO + query-uri pregatite.
- Validarea multiplu cantitate este implementata atat in JS cat si in PHP.
- Exportul PDF foloseste biblioteca mPDF.
