<?php

declare(strict_types=1);

namespace Postio\Exception;

/** 403 — origin / IP / key-restriction blocked the request. */
class PostioForbiddenException extends PostioException {}
