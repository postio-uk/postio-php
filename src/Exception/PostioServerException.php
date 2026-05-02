<?php

declare(strict_types=1);

namespace Postio\Exception;

/** 5xx — server-side failure. Retried by default before surfacing. */
class PostioServerException extends PostioException {}
