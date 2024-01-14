<?php

declare(strict_types=1);

namespace sgoettsch\FritzboxAHA;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use SimpleXMLElement;

/**
 * Class FritzboxAHA
 * @package sgoettsch\FritzboxAHA
 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
 */
class FritzboxAHA
{
    /** @noinspection HttpUrlsUsage */
    private string $loginUrl = "http://%s/login_sid.lua";
    /** @noinspection HttpUrlsUsage */
    private string $ahaUrl = "http://%s/webservices/homeautoswitch.lua?switchcmd=%s&sid=%s";
    private string $host;
    private bool $useSsl;
    private bool $checkCert;
    private string $user;
    private string $password;
    private string $sid;

    /**
     * @throws Exception|GuzzleException
     */
    public function login(
        string $host,
        string $user,
        string $password,
        bool $useSsl = false,
        bool $checkCert = true
    ): void {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->useSsl = $useSsl;
        $this->checkCert = $checkCert;
        $this->sid = $this->getSessionId();
    }

    public function getChallengeResponse(string $challenge): string
    {
        return $challenge . "-" .
            md5(
                mb_convert_encoding(
                    $challenge . "-" . $this->password,
                    "UTF-16LE",
                    "UTF-8"
                )
            );
    }

    /**
     * @throws Exception|GuzzleException
     */
    private function getSessionId(): string
    {
        $url = sprintf($this->loginUrl, $this->host);

        if ($this->useSsl) {
            $url = (string)preg_replace("/^http:/", "https:", $url);
        }

        $resp = $this->doRequest($url);

        if (empty($resp)) {
            throw new Exception('Failed to get sid');
        }

        $sess = simplexml_load_string($resp);

        if (isset($sess->Challenge, $sess->SID) && $sess->SID == "0000000000000000") {
            $challenge = (string)$sess->Challenge;
            $response = $this->getChallengeResponse($challenge);

            $login = $this->doRequest(
                $url.'?username=' . $this->user . '&response=' . $response
            );

            if (empty($login)) {
                throw new Exception('Could not get sid');
            }

            $sess = simplexml_load_string($login);
        }

        if (!isset($sess->SID)) {
            throw new Exception('Could not get sid');
        }

        return (string)$sess->SID;
    }

    /**
     * Set session id
     */
    public function getSid(): string
    {
        return $this->sid;
    }

    /**
     * Set session id
     */
    public function setSid(string $sid): void
    {
        $this->sid = $sid;
    }

    /**
     * @throws Exception
     */
    private function sendCommand(string $cmd, string $ain = "", string $param = ""): string
    {
        if ($this->sid && $this->sid != "0000000000000000") {
            $url = sprintf($this->ahaUrl, $this->host, $cmd, $this->sid, $ain, $param);

            if ($this->useSsl) {
                $url = (string)preg_replace("/^http:/", "https:", $url);
            }

            if ($ain) {
                $url .= sprintf("&ain=%s", $ain);
            }

            if ($param) {
                $url .= sprintf("&param=%d", $param);
            }

            $resp = $this->doRequest($url);

            if (!empty($resp)) {
                return trim($resp);
            }
        }

        throw new Exception($cmd.' failed');
    }

    /**
     * Returns information for all known devices
     * @throws Exception
     */
    public function getDeviceList(): SimpleXMLElement|bool
    {
        $resp = $this->sendCommand("getdevicelistinfos");

        if ($resp) {
            return simplexml_load_string($resp);
        }

        return false;
    }

    /**
     * Gets current temperature for device or group
     * @throws Exception
     */
    public function getTemperature(string $ain): float|int
    {
        return (int)$this->sendCommand("gettemperature", $ain) / 10;
    }

    /**
     * @throws Exception
     */
    private function getTemperatureHkr(string $ain, string $type): float|int|string
    {
        $temp = (int)$this->sendCommand($type, $ain);

        if ($temp == 254) {
            return "on";
        }

        if ($temp == 253) {
            return "off";
        }

        return $temp / 2;
    }

    /**
     * Gets aimed temperature for device or group
     * @throws Exception
     */
    public function getTemperatureSoll(string $ain): float|int|string
    {
        return $this->getTemperatureHkr($ain, "gethkrtsoll");
    }

    /**
     * Gets temperature for comfort-heating interval
     * @throws Exception
     */
    public function getTemperatureComfort(string $ain): float|int|string
    {
        return $this->getTemperatureHkr($ain, "gethkrkomfort");
    }

    /**
     * Gets temperature for non-heating interval
     * @throws Exception
     */
    public function getTemperatureLow(string $ain): float|int|string
    {
        return $this->getTemperatureHkr($ain, "gethkrabsenk");
    }

    /**
     * Sets temperature for device or group
     * @throws Exception
     */
    public function setTemperature(string $ain, int $temp): bool|string
    {
        if ($temp >= 8 && $temp <= 28) {
            $param = (string)floor($temp * 2);
            return $this->sendCommand("sethkrtsoll", $ain, $param);
        }

        if ($temp == 253 || $temp == 254) {
            return $this->sendCommand("sethkrtsoll", $ain, (string)$temp);
        }

        return false;
    }

    /**
     * Turns heating on for device or group
     * @throws Exception
     */
    public function setHeatingOn(string $ain): bool|string
    {
        return $this->setTemperature($ain, 254);
    }

    /**
     * Turns heating off for device or group
     * @throws Exception
     */
    public function setHeatingOff(string $ain): bool|string
    {
        return $this->setTemperature($ain, 253);
    }

    /**
     * Returns all known devices
     * @throws Exception
     */
    public function getAllDevices(): array
    {
        $devices = $this->getDeviceList();

        if (!isset($devices->device)) {
            throw new Exception('Could not get device list');
        }

        $ret = [];

        foreach ($devices->device as $device) {
            $ret[] = [
                "name" => (string)$device->name,
                "aid" => (string)$device["identifier"],
                "type" => (string)$device["functionbitmask"],
            ];
        }

        return $ret;
    }

    /**
     * Returns all known device groups
     * @throws Exception
     */
    public function getAllGroups(): SimpleXMLElement
    {
        $devices = $this->getDeviceList();

        if (!isset($devices->group)) {
            throw new Exception('Could not get devices');
        }

        return $devices->group;
    }

    /**
     * Returns AIN/MAC of all known switches
     * @throws Exception
     */
    public function getAllSwitches(): array
    {
        $switches = $this->sendCommand("getswitchlist");
        return explode(",", $switches);
    }

    /**
     * Turn switch on
     * @throws Exception
     */
    public function setSwitchOn(string $ain): bool|string
    {
        return $this->sendCommand("setswitchon", $ain);
    }

    /**
     * Turn switch off
     * @throws Exception
     */
    public function setSwitchOff(string $ain): bool|string
    {
        return $this->sendCommand("setswitchoff", $ain);
    }

    /**
     * Toggle switch state
     * @throws Exception
     */
    public function setSwitchToggle(string $ain): bool|string
    {
        return $this->sendCommand("setswitchtoggle", $ain);
    }

    /**
     * Get power state of switch
     * @throws Exception
     */
    public function getSwitchState(string $ain): bool|string
    {
        return $this->sendCommand("getswitchstate", $ain);
    }

    /**
     * Is the switch connected
     * @throws Exception
     */
    public function isSwitchPresent(string $ain): bool
    {
        return (bool)$this->sendCommand("getswitchpresent", $ain);
    }

    /**
     * Get current power consumption in mW
     * @throws Exception
     */
    public function getSwitchPower(string $ain): bool|string
    {
        return $this->sendCommand("getswitchpower", $ain);
    }

    /**
     * Get total power consumption since last reset in Wh
     * @throws Exception
     */
    public function getSwitchEnergy(string $ain): bool|string
    {
        return $this->sendCommand("getswitchenergy", $ain);
    }

    /**
     * Get switch name
     * @throws Exception
     */
    public function getSwitchName(string $ain): bool|string
    {
        return $this->sendCommand("getswitchname", $ain);
    }

    /**
     * @throws GuzzleException
     */
    private function doRequest(string $url, string $payload = ''): string
    {
        $client = new Client(['verify' => $this->checkCert]);
        $request = new Request('GET', $url, [], $payload);

        return (string)$client->send($request, ['timeout' => 10])->getBody();
    }
}
