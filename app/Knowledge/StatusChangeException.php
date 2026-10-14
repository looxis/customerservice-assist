<?php

namespace App\Knowledge;

use RuntimeException;

/**
 * A status change of a knowledge document that was refused; the message is meant for the admin.
 */
class StatusChangeException extends RuntimeException {}
