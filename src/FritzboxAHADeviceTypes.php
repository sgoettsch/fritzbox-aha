<?php

declare(strict_types=1);

namespace sgoettsch\FritzboxAHA;

enum FritzboxAHADeviceTypes: int
{
    case FRITZ_DECT_200 = 35712;
    case FRITZ_DECT_300 = 320;
    case FRITZ_DECT_440 = 1048864;
    case FRITZ_DECT_500 = 237572;
}
