<?php

declare(strict_types=1);

namespace sgoettsch\FritzboxAHA;

enum FritzboxAHADeviceTypes: int
{
    case FRITZ_DECT_200 = 35712;
    case FRITZ_DECT_300 = 320;
    case FRITZ_DECT_440 = 1048864;
    case FRITZ_DECT_500 = 237572;

    public function getValue(): int
    {
        return match ($this) {
            self::FRITZ_DECT_200 => 35712,
            self::FRITZ_DECT_300 => 320,
            self::FRITZ_DECT_440 => 1048864,
            self::FRITZ_DECT_500 => 237572
        };
    }
}
