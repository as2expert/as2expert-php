<?php

declare(strict_types=1);

namespace As2Expert;

/** The request never completed (connection / timeout / TLS / decode). */
class TransportException extends ApiException {}
