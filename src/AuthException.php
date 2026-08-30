<?php

declare(strict_types=1);

namespace As2Expert;

/** 401 / 403 — missing/invalid token or insufficient scope. */
class AuthException extends ApiException {}
