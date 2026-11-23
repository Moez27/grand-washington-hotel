<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261123215746 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE amenity (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(60) NOT NULL, icon VARCHAR(50) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AB6079635E237E06 ON amenity (name)');
        $this->addSql('CREATE TABLE category (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(80) NOT NULL, slug VARCHAR(100) NOT NULL, description CLOB DEFAULT NULL, icon VARCHAR(50) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_64C19C15E237E06 ON category (name)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_64C19C1989D9B62 ON category (slug)');
        $this->addSql('CREATE TABLE hotel_service (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description CLOB NOT NULL, price NUMERIC(10, 2) NOT NULL, pricing_type VARCHAR(20) NOT NULL, icon VARCHAR(50) NOT NULL, image VARCHAR(255) DEFAULT NULL, is_active BOOLEAN NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_32D784FB5E237E06 ON hotel_service (name)');
        $this->addSql('CREATE TABLE reservation (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, reference VARCHAR(20) NOT NULL, check_in DATE NOT NULL, check_out DATE NOT NULL, guests SMALLINT NOT NULL, total_price NUMERIC(10, 2) NOT NULL, status VARCHAR(20) NOT NULL, special_request CLOB DEFAULT NULL, created_at DATETIME NOT NULL, user_id INTEGER NOT NULL, room_id INTEGER NOT NULL, CONSTRAINT FK_42C84955A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_42C8495554177093 FOREIGN KEY (room_id) REFERENCES room (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_42C84955AEA34913 ON reservation (reference)');
        $this->addSql('CREATE INDEX idx_reservation_dates ON reservation (room_id, check_in, check_out)');
        $this->addSql('CREATE INDEX IDX_42C84955A76ED395 ON reservation (user_id)');
        $this->addSql('CREATE INDEX IDX_42C8495554177093 ON reservation (room_id)');
        $this->addSql('CREATE TABLE reservation_hotel_service (reservation_id INTEGER NOT NULL, hotel_service_id INTEGER NOT NULL, PRIMARY KEY (reservation_id, hotel_service_id), CONSTRAINT FK_DCBBF4A6B83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_DCBBF4A69888430B FOREIGN KEY (hotel_service_id) REFERENCES hotel_service (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_DCBBF4A6B83297E7 ON reservation_hotel_service (reservation_id)');
        $this->addSql('CREATE INDEX IDX_DCBBF4A69888430B ON reservation_hotel_service (hotel_service_id)');
        $this->addSql('CREATE TABLE review (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, rating SMALLINT NOT NULL, comment CLOB NOT NULL, is_approved BOOLEAN NOT NULL, created_at DATETIME NOT NULL, user_id INTEGER NOT NULL, room_id INTEGER NOT NULL, CONSTRAINT FK_794381C6A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_794381C654177093 FOREIGN KEY (room_id) REFERENCES room (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_794381C6A76ED395 ON review (user_id)');
        $this->addSql('CREATE INDEX IDX_794381C654177093 ON review (room_id)');
        $this->addSql('CREATE TABLE room (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(120) NOT NULL, slug VARCHAR(140) NOT NULL, short_description VARCHAR(255) NOT NULL, description CLOB NOT NULL, price_per_night NUMERIC(10, 2) NOT NULL, capacity SMALLINT NOT NULL, size SMALLINT NOT NULL, bed_type VARCHAR(60) NOT NULL, image VARCHAR(255) DEFAULT NULL, is_active BOOLEAN NOT NULL, is_featured BOOLEAN NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, category_id INTEGER NOT NULL, CONSTRAINT FK_729F519B12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_729F519B5E237E06 ON room (name)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_729F519B989D9B62 ON room (slug)');
        $this->addSql('CREATE INDEX IDX_729F519B12469DE2 ON room (category_id)');
        $this->addSql('CREATE TABLE room_amenity (room_id INTEGER NOT NULL, amenity_id INTEGER NOT NULL, PRIMARY KEY (room_id, amenity_id), CONSTRAINT FK_4742C58354177093 FOREIGN KEY (room_id) REFERENCES room (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4742C5839F9F1305 FOREIGN KEY (amenity_id) REFERENCES amenity (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_4742C58354177093 ON room_amenity (room_id)');
        $this->addSql('CREATE INDEX IDX_4742C5839F9F1305 ON room_amenity (amenity_id)');
        $this->addSql('CREATE TABLE "user" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(60) NOT NULL, last_name VARCHAR(60) NOT NULL, phone VARCHAR(20) DEFAULT NULL, is_blocked BOOLEAN NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email)');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE amenity');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE hotel_service');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE reservation_hotel_service');
        $this->addSql('DROP TABLE review');
        $this->addSql('DROP TABLE room');
        $this->addSql('DROP TABLE room_amenity');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
