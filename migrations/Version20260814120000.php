<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260814120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix track_usage_since column type comment on sylius_promotion_coupon (Sylius\Bundle\CoreBundle\Migrations\Version20260616120000 created it without the datetime_immutable DC2Type marker, causing doctrine:schema:validate to report a mismatch).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE sylius_promotion_coupon CHANGE track_usage_since track_usage_since DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sylius_promotion_coupon CHANGE track_usage_since track_usage_since DATETIME DEFAULT NULL');
    }
}
