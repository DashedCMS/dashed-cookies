<?php

declare(strict_types=1);

namespace Dashed\DashedCookies\PersonalData;

use Dashed\DashedCore\PersonalData\PersonalDataExport;
use Dashed\DashedCore\PersonalData\PersonalDataFinding;
use Dashed\DashedCore\PersonalData\PersonalDataSubject;
use Dashed\DashedCore\PersonalData\PersonalDataProvider;

/**
 * Het toestemmingslogboek van de cookiebanner. Dat is gepseudonimiseerd: er
 * staat een willekeurig toestemmings-ID (de cookie in de browser van de
 * bezoeker), een HMAC van het IP-adres en een ingekorte user-agent in, maar
 * nooit een e-mailadres of account. Op een e-mailadres is er dus niets te
 * vinden, en zonder koppeling ook niets te verwijderen.
 *
 * Deze provider zoekt daarom niets, maar meldt de bron wel in het overzicht,
 * zodat de beheerder ziet dat hij bekeken is en waarom er niets gebeurt. Wil
 * iemand zijn eigen toestemmingen zien of laten verwijderen, dan moet hij het
 * toestemmings-ID uit zijn browser aanleveren; dat valt buiten dit
 * e-mailverzoek. De regels verdwijnen vanzelf met de bewaartermijn.
 */
class CookiesPersonalDataProvider extends PersonalDataProvider
{
    public function key(): string
    {
        return 'dashed-cookies';
    }

    public function label(): string
    {
        return __('Cookietoestemmingen');
    }

    public function priority(): int
    {
        return 300;
    }

    public function find(PersonalDataSubject $subject): array
    {
        return $this->explanation();
    }

    public function export(PersonalDataSubject $subject, PersonalDataExport $export): void
    {
        // Niets op e-mailadres te vinden, zie de uitleg bovenaan.
    }

    public function erase(PersonalDataSubject $subject): array
    {
        return $this->explanation();
    }

    /** @return array<int, PersonalDataFinding> */
    protected function explanation(): array
    {
        if (! $this->hasTable('dashed__cookie_consents')) {
            return [];
        }

        return [
            PersonalDataFinding::keep(
                'consents',
                __('Toestemmingslogboek'),
                0,
                __('Gepseudonimiseerd: alleen een toestemmings-ID, geen e-mailadres. Niet te koppelen aan deze persoon; de regels verlopen met de bewaartermijn.'),
            ),
        ];
    }
}
