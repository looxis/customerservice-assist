<?php

namespace App\Http\Requests;

/**
 * The input of the page "Einstellungen > Reklamations-Automatik" (PROJ-85): the same checks as the other settings pages,
 * only admins may send it.
 */
class ClaimReplySettingsRequest extends MailSettingsRequest
{
    public const string PAGE = 'claim_reply';
}
