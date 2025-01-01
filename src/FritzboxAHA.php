<?php

declare(strict_types=1);

namespace sgoettsch\FritzboxAHA;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;

class FritzboxAHA
{
    private Client $client;
    /** @noinspection HttpUrlsUsage */
    private string $loginUrl = "http://%s/login_sid.lua";
    /** @noinspection HttpUrlsUsage */
    private string $ahaUrl = "http://%s/webservices/homeautoswitch.lua?switchcmd=%s&sid=%s";
    private string $host;
    private bool $useSsl;
    private string $user;
    private string $password;
    private string $sid;

    /** @var list<FritzboxAHADevice> */
    private array $devices;

    public function __construct(
        ?Client $client = null,
        bool $checkCert = true
    ) {
        if (!is_null($client)) {
            $this->client = $client;
            return;
        }

        $this->client = new Client(['verify' => $checkCert]);
    }

    /**
     * @throws Exception|GuzzleException
     */
    public function login(
        string $host,
        string $user,
        string $password,
        bool $useSsl = false
    ): void {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->useSsl = $useSsl;
        $this->setSid($this->getSessionId());
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
                $url . '?username=' . $this->user . '&response=' . $response
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

    public function getSid(): string
    {
        return $this->sid;
    }

    private function setSid(string $sid): void
    {
        $this->sid = $sid;
    }

    /**
     * @throws Exception|GuzzleException
     */
    private function sendCommand(string $cmd, string $ain = "", string $param = ""): string
    {
        if ($this->sid && $this->sid != "0000000000000000") {
            $url = sprintf($this->ahaUrl, $this->host, $cmd, $this->sid, $ain, $param);

            if ($this->useSsl) {
                $url = (string)preg_replace("/^http:/", "https:", $url);
            }

            if ($ain) {
                $url .= sprintf("&ain=%s", $this->cleanIdentifier($ain));
            }

            if ($param) {
                $url .= sprintf("&param=%d", $param);
            }

            $resp = $this->doRequest($url);

            if (!empty($resp)) {
                return trim($resp);
            }
        }

        throw new Exception($cmd . ' failed');
    }

    /**
     * @return list<FritzboxAHADevice>
     * @throws GuzzleException|Exception
     */
    public function getAllDevices(bool $forceRefresh = false): array
    {
        if (!empty($this->devices)) {
            return $this->devices;
        }

        $requestResponse = $this->sendCommand("getdevicelistinfos");

        if (!$requestResponse) {
            throw new Exception('Could not get device list');
        }

        $devices = simplexml_load_string($requestResponse);

        if (!isset($devices->device)) {
            throw new Exception('Could not get device list');
        }

        $deviceList = [];

        foreach ($devices->device as $deviceData) {
            $deviceList[] = new FritzboxAHADevice($deviceData);
        }

        $this->devices = $deviceList;

        return $deviceList;
    }

    /**
     * @throws GuzzleException|Exception
     */
    public function getDevice(string $identifier): FritzboxAHADevice
    {
        $devices = $this->getAllDevices();
        foreach ($devices as $device) {
            /** @var FritzboxAHADevice $device */
            if ($device->getIdentifier() === $identifier ||
                $this->cleanIdentifier($device->getIdentifier()) === $this->cleanIdentifier($identifier)
            ) {
                return $device;
            }
        }

        throw new Exception('Could not get device');
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @throws GuzzleException
     */
    private function doRequest(string $url): string
    {
        $request = new Request('GET', $url);

        return (string)$this->client->send($request, ['timeout' => 10])->getBody();
    }

    private function cleanIdentifier(string $identifier): string
    {
        return str_replace(' ', '', $identifier);
    }

    /**
     * Sets temperature for device or group
     *
     * $temp can be:
     * - 8 - 28 to set to this degree
     * - 254 = ON , 253 = OFF
     *
     * @throws Exception|GuzzleException
     */
    public function setTemperature(string $identifier, int $temp): bool|string
    {
        if ($temp >= 8 && $temp <= 28) {
            $param = floor($temp * 2);
            return $this->sendCommand("sethkrtsoll", $identifier, (string)$param);
        }

        if ($temp == 253 || $temp == 254) {
            return $this->sendCommand("sethkrtsoll", $identifier, (string)$temp);
        }

        return false;
    }

    /**
     * Set max temperature for device or group
     *
     * @throws Exception|GuzzleException
     * @noinspection PhpUnused
     */
    public function setMaxTemperature(string $identifier): bool|string
    {
        return $this->setTemperature($identifier, 254);
    }

    /**
     * Turns heating off for device or group
     *
     * @throws Exception|GuzzleException
     * @noinspection PhpUnused
     */
    public function setHeatingOff(string $identifier): bool|string
    {
        return $this->setTemperature($identifier, 253);
    }

    /**
     * Returns all known device groups
     *
     * @return list<array{name: string, identifier: string}>
     * @throws Exception|GuzzleException
     */
    public function getAllGroups(): array
    {
        if (!empty($this->groups)) {
            return $this->groups;
        }

        $requestResponse = $this->sendCommand("getdevicelistinfos");

        if (!$requestResponse) {
            throw new Exception('Could not get group list');
        }

        $groups = simplexml_load_string($requestResponse);

        if (!isset($groups->group)) {
            throw new Exception('Could not get group list');
        }

        $groupsList = [];

        foreach ($groups->group as $group) {
            $groupsList[] = [
                'name' => $group->name->__toString(),
                'identifier' => isset($group['identifier']) ? $group['identifier']->__toString() : '',
            ];
        }

        return $groupsList;
    }

    /**
     * Turn switch on
     *
     * @throws Exception|GuzzleException
     * @noinspection PhpUnused
     */
    public function setSwitchOn(string $identifier): bool|string
    {
        return $this->sendCommand("setswitchon", $identifier);
    }

    /**
     * Turn switch off
     *
     * @throws Exception|GuzzleException
     * @noinspection PhpUnused
     */
    public function setSwitchOff(string $identifier): bool|string
    {
        return $this->sendCommand("setswitchoff", $identifier);
    }

    /**
     * Toggle switch state
     *
     * @throws Exception|GuzzleException
     * @noinspection PhpUnused
     */
    public function setSwitchToggle(string $identifier): bool|string
    {
        return $this->sendCommand("setswitchtoggle", $identifier);
    }

    /**
     * Get power state of switch
     *
     * @throws Exception|GuzzleException
     * @noinspection PhpUnused
     */
    public function getSwitchState(string $identifier): bool|string
    {
        return $this->sendCommand("getswitchstate", $identifier);
    }
}
