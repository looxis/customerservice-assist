<?php

namespace App\Http\Requests;

/**
 * The input of the page "Einstellungen > WISMO-Automatik" (PROJ-82): the same checks as the other settings pages,
 * only admins may send it.
 */
class WismoSettingsRequest extends MailSettingsRequest
{
    public const string PAGE = 'wismo';
}
