-- Create database and application user
CREATE DATABASE IF NOT EXISTS sgw CHARACTER SET latin1 COLLATE latin1_swedish_ci;

CREATE USER IF NOT EXISTS 'sgw'@'localhost' IDENTIFIED BY 'sgwpass';
GRANT ALL PRIVILEGES ON sgw.* TO 'sgw'@'localhost';
FLUSH PRIVILEGES;
