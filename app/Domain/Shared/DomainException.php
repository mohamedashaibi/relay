<?php

namespace App\Domain\Shared;

use RuntimeException;

/** Base for business-rule failures. Delivery layers map subclasses to HTTP / MCP errors. */
class DomainException extends RuntimeException {}
