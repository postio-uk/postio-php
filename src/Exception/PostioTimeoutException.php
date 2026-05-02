<?php

declare(strict_types=1);

namespace Postio\Exception;

/** Local request timeout — no response received within the configured timeout. */
class PostioTimeoutException extends PostioException {}
