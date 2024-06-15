<?php

declare(strict_types=1);

include_once __DIR__ . '/../vendor/autoload.php';

use sgoettsch\FritzboxAHA\FritzboxAHA;
use sgoettsch\FritzboxAHA\FritzboxAHADevice;

$aha = new FritzboxAHA();

$aha->login("fritz.box", "", "password");

echo "Session id: " . $aha->getSid() . "\n";

$devices = $aha->getAllDevices();
foreach ($devices as $key => $device) {
    /** @var FritzboxAHADevice $device */

    $batteryLevel = $device->getBatteryLevel();
    $humidity = $device->getHumidity();
    $measuredTemperature = $device->getMeasuredTemperature();
    $targetTemperature = $device->getTargetTemperature();

    echo 'Device ' . ($key + 1) . ': ' . $device->getName() . "\n";
    echo 'Identifier: ' . $device->getIdentifier() . "\n";
    echo 'Manufacturer: ' . $device->getManufacturer() . "\n";
    echo 'Product: ' . $device->getProductName() . "\n";
    echo 'Firmware Version: ' . $device->getFirmwareVersion() . "\n";

    if ($batteryLevel !== null) {
        echo 'Battery Level: ' . $batteryLevel . "%\n";
    }

    if ($humidity !== null) {
        echo 'Humidity: ' . $humidity . "%\n";
    }

    if ($measuredTemperature !== null) {
        echo 'Measured Temperature: ' . $measuredTemperature . " Degree\n";
    }

    if ($targetTemperature !== null) {
        echo 'Target Temperature: ' . ($targetTemperature === -1.0 ? 'off' : $targetTemperature . ' Degree') . "\n";
    }
    echo "\n\n";
}
