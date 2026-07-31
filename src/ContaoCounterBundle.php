<?php

declare(strict_types=1);

/**
 * Counter für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoCounterBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Symfony-Bundle-Klasse der Erweiterung.
 *
 * Enthält bewusst keine Logik: Sie meldet das Bundle lediglich beim Kernel an.
 * Die Registrierung im Contao Manager erledigt ContaoManager\Plugin.
 */
class ContaoCounterBundle extends Bundle
{
}
