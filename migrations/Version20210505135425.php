<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Booking;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210505135425 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        for($i=Booking::FIRST_SEAT;$i<=Booking::NUMBER_OF_SEATS;$i++){
            $this->addSql('INSERT INTO booking (seat) values ('.$i.')');
        }

    }

    public function down(Schema $schema): void
    {
        $this->addSql('TRUNCATE TABLE booking');
    }
}
