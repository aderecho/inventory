<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a whole import cannot run (bad header, missing lookup data, etc.),
 * as opposed to a single row failing validation.
 */
class InventoryImportException extends RuntimeException
{
}
