<?php

namespace App\Http\Requests;

/**
 * The input of the page "Einstellungen > Erkennungsregeln" (PROJ-49): the formats of order numbers and the
 * rules for article numbers. Checked as a whole before anything is saved.
 */
class DetectionSettingsRequest extends MailSettingsRequest
{
    public const string PAGE = 'detection';
}
