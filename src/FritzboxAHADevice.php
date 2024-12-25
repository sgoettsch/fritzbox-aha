<?php

/** @noinspection PhpUnused */
/** @noinspection SpellCheckingInspection */

declare(strict_types=1);

namespace sgoettsch\FritzboxAHA;

use SimpleXMLElement;

class FritzboxAHADevice
{
    private ?int $batteryLevel = null;
    private ?FritzboxAHADeviceTypes $deviceType;
    private string $firmwareVersion;
    private int $functionBitmask;
    private string $identifier;
    private bool $isBatteryLevelLow;
    private bool $isPresent;
    private bool $hasBattery;
    private ?int $humidity;
    private string $name;
    private string $manufacturer;
    private ?float $measuredTemperature;
    private string $productName;
    private ?float $targetTemperature;

    public function __construct(
        SimpleXMLElement $data
    ) {
        $this->setFunctionBitmask($data); // used by other functions so set this first
        $this->setDeviceType();
        $this->setHasBattery();

        $this->setBatteryLevel($data);
        $this->setFirmwareVersion($data);
        $this->setHumidity($data);
        $this->setIdentifier($data);
        $this->setIsBatteryLevelLow($data);
        $this->setIsPresent($data);
        $this->setName($data);
        $this->setManufacturer($data);
        $this->setMeasuredTemperature($data);
        $this->setProductName($data);
        $this->setTargetTemperature($data);
    }

    public function getBatteryLevel(): ?int
    {
        return $this->batteryLevel;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function hasBattery(): bool
    {
        return $this->hasBattery;
    }

    public function getHumidity(): ?int
    {
        return $this->humidity;
    }

    public function getFirmwareVersion(): string
    {
        return $this->firmwareVersion;
    }

    public function getFunctionBitmask(): ?int
    {
        return $this->functionBitmask;
    }

    public function getDeviceType(): ?FritzboxAHADeviceTypes
    {
        return $this->deviceType;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getManufacturer(): string
    {
        return $this->manufacturer;
    }

    public function getMeasuredTemperature(): ?float
    {
        return $this->measuredTemperature;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getTargetTemperature(): ?float
    {
        return $this->targetTemperature;
    }

    public function isBatteryLevelLow(): bool
    {
        return $this->isBatteryLevelLow;
    }

    public function isPresent(): bool
    {
        return $this->isPresent;
    }

    private function setBatteryLevel(SimpleXMLElement $data): void
    {
        if (isset($data->battery)) {
            $batteryLevel = $data->battery->__toString();
            $this->batteryLevel = empty($batteryLevel) ? null : (int)$batteryLevel;
        }
    }

    private function setIsBatteryLevelLow(SimpleXMLElement $data): void
    {
        if (isset($data->batterylow)) {
            $isBatteryLevelLow = $data->batterylow->__toString();
            $this->isBatteryLevelLow = (int)$isBatteryLevelLow === 1;
        }
    }

    private function setFirmwareVersion(SimpleXMLElement $data): void
    {
        if (isset($data['fwversion'])) {
            $this->firmwareVersion = $data['fwversion']->__toString();
        }
    }

    private function setFunctionBitmask(SimpleXMLElement $data): void
    {
        if (isset($data['functionbitmask'])) {
            $this->functionBitmask = (int)$data['functionbitmask']->__toString();
        }
    }

    private function setDeviceType(): void
    {
        $this->deviceType = match ($this->getFunctionBitmask()) {
            35712 => FritzboxAHADeviceTypes::FRITZ_DECT_200,
            320 => FritzboxAHADeviceTypes::FRITZ_DECT_300,
            3146016 => FritzboxAHADeviceTypes::FRITZ_DECT_440,
            237572 => FritzboxAHADeviceTypes::FRITZ_DECT_500,
            default => null,
        };
    }

    private function setHasBattery(): void
    {
        $this->hasBattery = match ($this->getFunctionBitmask()) {
            320, 3146016 => true,
            default => false,
        };
    }

    private function setHumidity(SimpleXMLElement $data): void
    {
        $this->humidity = match ($this->getFunctionBitmask()) {
            3146016 => (int)$data->humidity->rel_humidity->__toString(),
            default => null,
        };
    }

    private function setIdentifier(SimpleXMLElement $data): void
    {
        if (isset($data['identifier'])) {
            $this->identifier = $data['identifier']->__toString();
        }
    }

    private function setIsPresent(SimpleXMLElement $data): void
    {
        if (isset($data->present)) {
            $isPresent = $data->present->__toString();
            $this->isPresent = (int)$isPresent === 1;
        }
    }

    private function setName(SimpleXMLElement $data): void
    {
        if (isset($data->name)) {
            $this->name = $data->name->__toString();
        }
    }

    private function setManufacturer(SimpleXMLElement $data): void
    {
        if (isset($data['manufacturer'])) {
            $this->manufacturer = $data['manufacturer']->__toString();
        }
    }

    private function setMeasuredTemperature(SimpleXMLElement $data): void
    {
        $this->measuredTemperature = match ($this->getFunctionBitmask()) {
            320, 35712, 3146016 => (float)$data->temperature->celsius->__toString() / 10,
            default => null,
        };
    }

    private function setProductName(SimpleXMLElement $data): void
    {
        if (isset($data['productname'])) {
            $this->productName = $data['productname']->__toString();
        }
    }

    private function setTargetTemperature(SimpleXMLElement $data): void
    {
        switch ($this->getFunctionBitmask()) {
            case 320:
                $targetTemperature = (int) $data->hkr->tsoll->__toString();
                if ($targetTemperature === 253) {
                    // disabled, not heating
                    $this->targetTemperature = -1.0;
                    return;
                }

                $this->targetTemperature = (float) ($targetTemperature / 2);
                break;
            default:
                $this->targetTemperature = null;
                break;
        }
    }
}
