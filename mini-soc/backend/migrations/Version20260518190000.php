<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260518190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Mini SOC schéma initial (PostgreSQL 15)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE users (id SERIAL NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_users_email ON users (email)');

        $this->addSql('CREATE TABLE auth_logs (id SERIAL NOT NULL, ip VARCHAR(45) NOT NULL, user_agent TEXT DEFAULT NULL, email_hash VARCHAR(255) NOT NULL, status VARCHAR(10) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');

        $this->addSql('CREATE TABLE alerts (id SERIAL NOT NULL, type VARCHAR(50) NOT NULL, severity VARCHAR(10) NOT NULL, status VARCHAR(20) DEFAULT \'open\' NOT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, resolved_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, auth_log_id INT NOT NULL, user_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_alerts_auth_log ON alerts (auth_log_id)');
        $this->addSql('CREATE INDEX IDX_alerts_user ON alerts (user_id)');
        $this->addSql('ALTER TABLE alerts ADD CONSTRAINT FK_alerts_auth_log FOREIGN KEY (auth_log_id) REFERENCES auth_logs (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE alerts ADD CONSTRAINT FK_alerts_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE alert_comments (id SERIAL NOT NULL, content TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, alert_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_comments_alert ON alert_comments (alert_id)');
        $this->addSql('CREATE INDEX IDX_comments_user ON alert_comments (user_id)');
        $this->addSql('ALTER TABLE alert_comments ADD CONSTRAINT FK_comments_alert FOREIGN KEY (alert_id) REFERENCES alerts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE alert_comments ADD CONSTRAINT FK_comments_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE ip_reputation_cache (id SERIAL NOT NULL, ip VARCHAR(45) NOT NULL, score SMALLINT NOT NULL, country VARCHAR(3) DEFAULT NULL, isp VARCHAR(255) DEFAULT NULL, is_tor BOOLEAN DEFAULT false NOT NULL, is_vpn BOOLEAN DEFAULT false NOT NULL, last_checked TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_ip_rep_ip ON ip_reputation_cache (ip)');

        $this->addSql('CREATE TABLE blocked_ips (ip VARCHAR(45) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(ip))');

        $this->addSql('CREATE TABLE abuseip_quota (day DATE NOT NULL, cnt INT NOT NULL, PRIMARY KEY(day))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE alert_comments CASCADE');
        $this->addSql('DROP TABLE alerts CASCADE');
        $this->addSql('DROP TABLE blocked_ips CASCADE');
        $this->addSql('DROP TABLE abuseip_quota CASCADE');
        $this->addSql('DROP TABLE ip_reputation_cache CASCADE');
        $this->addSql('DROP TABLE auth_logs CASCADE');
        $this->addSql('DROP TABLE users CASCADE');
    }
}
