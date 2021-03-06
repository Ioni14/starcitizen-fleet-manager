<?php

namespace App\Domain\Exception;

use App\Domain\ShipId;
use App\Domain\UserId;
use Throwable;

class NotFoundShipException extends DomainException
{
    public static bool $notFound = true;

    public function __construct(
        UserId $userId,
        ShipId $shipId,
        string $userMessage = '',
        array $context = [],
        $message = '',
        $code = 0,
        Throwable $previous = null,
    ) {
        $context['userId'] = $userId;
        $context['shipId'] = $shipId;
        parent::__construct(
            'not_found_ship',
            $userMessage ?: 'This ship does not exist for this user.',
            $context,
            $message ?: sprintf('Unable to find ship %s of user %s.', $shipId, $userId),
            $code,
            $previous,
        );
    }
}
