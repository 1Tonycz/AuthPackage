<?php

declare(strict_types=1);

namespace Matodo\Auth\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260709000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'matodo/auth: uživatelé, skupiny, oprávnění, pokusy o přihlášení, historie změn';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE auth_user (
                id INT AUTO_INCREMENT NOT NULL,
                username VARCHAR(100) NOT NULL,
                email VARCHAR(255) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                last_login_at DATETIME DEFAULT NULL,
                UNIQUE INDEX uniq_auth_user_username (username),
                UNIQUE INDEX uniq_auth_user_email (email),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE auth_group (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(100) NOT NULL,
                description TEXT DEFAULT NULL,
                is_system TINYINT(1) NOT NULL DEFAULT 0,
                UNIQUE INDEX uniq_auth_group_name (name),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE auth_user_group (
                user_id INT NOT NULL,
                group_id INT NOT NULL,
                INDEX idx_aug_user (user_id),
                INDEX idx_aug_group (group_id),
                PRIMARY KEY (user_id, group_id),
                CONSTRAINT fk_aug_user FOREIGN KEY (user_id) REFERENCES auth_user (id) ON DELETE CASCADE,
                CONSTRAINT fk_aug_group FOREIGN KEY (group_id) REFERENCES auth_group (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE auth_permission (
                id INT AUTO_INCREMENT NOT NULL,
                resource VARCHAR(100) NOT NULL,
                privilege VARCHAR(100) NOT NULL,
                description VARCHAR(255) DEFAULT NULL,
                package VARCHAR(100) DEFAULT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                UNIQUE INDEX uniq_resource_privilege (resource, privilege),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE auth_group_permission (
                group_id INT NOT NULL,
                permission_id INT NOT NULL,
                INDEX idx_agp_group (group_id),
                INDEX idx_agp_permission (permission_id),
                PRIMARY KEY (group_id, permission_id),
                CONSTRAINT fk_agp_group FOREIGN KEY (group_id) REFERENCES auth_group (id) ON DELETE CASCADE,
                CONSTRAINT fk_agp_permission FOREIGN KEY (permission_id) REFERENCES auth_permission (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE auth_login_attempt (
                id INT AUTO_INCREMENT NOT NULL,
                username VARCHAR(100) NOT NULL,
                ip VARCHAR(45) NOT NULL,
                success TINYINT(1) NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_username_ip_created (username, ip, created_at),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE auth_audit_log (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT DEFAULT NULL,
                action VARCHAR(100) NOT NULL,
                subject_type VARCHAR(100) DEFAULT NULL,
                subject_id INT DEFAULT NULL,
                data JSON DEFAULT NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_audit_created (created_at),
                INDEX idx_audit_user (user_id),
                PRIMARY KEY (id),
                CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES auth_user (id) ON DELETE SET NULL
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE auth_audit_log');
        $this->addSql('DROP TABLE auth_login_attempt');
        $this->addSql('DROP TABLE auth_group_permission');
        $this->addSql('DROP TABLE auth_permission');
        $this->addSql('DROP TABLE auth_user_group');
        $this->addSql('DROP TABLE auth_user');
    }
}
