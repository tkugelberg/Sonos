<?php

declare(strict_types=1);

class SonosAccessDouble
{
    private $calls = [];
    private $return = [];
    private $IP = '';

    public function AddToQueue($file, $meta = '')
    {
        $this->addCall(__FUNCTION__);
    }

    public function BrowseContentDirectory($objectID = 'SQ:', $browseFlag = 'BrowseDirectChildren', $requestedCount = 100, $startingIndex = 0, $filter = '', $sortCriteria = ''): array
    {
        $this->addCall(__FUNCTION__);
    }

    public function ClearQueue()
    {
        $this->addCall(__FUNCTION__);
    }

    public function DelegateGroupCoordinationTo(string $NewCoordinator, bool $RejoinGroup)
    {
        $this->addCall(__FUNCTION__);
    }

    public function GetBass(): int
    {
        $this->addCall(__FUNCTION__);
    }

    public function GetCrossfade(): bool
    {
        $this->addCall(__FUNCTION__);
    }

    public function GetDialogLevel(): bool
    {
        $this->addCall(__FUNCTION__);
    }

    public function GetLoudness(): bool
    {
        $this->addCall(__FUNCTION__);
    }

    public function GetMediaInfo(): array
    {
        $this->addCall(__FUNCTION__);
    }

    public function GetMute(): bool
    {
        $this->addCall(__FUNCTION__);
    }

    public function GetNightMode(): bool
    {
        $this->addCall(__FUNCTION__);
    }

    public function GetOutputFixed(): bool
    {
        $this->addCall(__FUNCTION__);
    }

    public function GetPositionInfo(): array
    {
        $this->addCall(__FUNCTION__);
    }

    public function GetSleeptimer(): string
    {
        $this->addCall(__FUNCTION__);
    }

    public function GetTransportInfo(): int
    {
        $this->addCall(__FUNCTION__);
        return $this->getReturn(__FUNCTION__);
    }

    public function GetTransportSettings(): int
    {
        $this->addCall(__FUNCTION__);
    }

    public function GetTreble(): int
    {
        $this->addCall(__FUNCTION__);
    }

    public function GetVolume($channel = 'Master'): int
    {
        $this->addCall(__FUNCTION__);
    }

    public function GetZoneGroupAttributes(): array
    {
        $this->addCall(__FUNCTION__);
    }

    public function GetZoneGroupState(): string
    {
        $this->addCall(__FUNCTION__);
    }

    public function Next()
    {
        $this->addCall(__FUNCTION__);
    }

    public function Pause()
    {
        $this->addCall(__FUNCTION__);
    }

    public function Play()
    {
        $this->addCall(__FUNCTION__);
    }

    public function Previous()
    {
        $this->addCall(__FUNCTION__);
    }

    public function RampToVolume($rampType, $volume)
    {
        $this->addCall(__FUNCTION__);
    }

    public function RemoveFromQueue($track)
    {
        $this->addCall(__FUNCTION__);
    }

    public function Rewind()
    {
        $this->addCall(__FUNCTION__);
    }

    public function Seek($unit, $target)
    {
        $this->addCall(__FUNCTION__);
    }

    public function SetAVTransportURI($tspuri, $MetaData = '')
    {
        $this->addCall(__FUNCTION__);
    }

    public function SetBass($bass)
    {
        $this->addCall(__FUNCTION__);
    }

    public function SetCrossfade($crossfade)
    {
        $this->addCall(__FUNCTION__);
    }

    public function SetDialogLevel($dialogLevel)
    {
        $this->addCall(__FUNCTION__);
    }

    public function SetLoudness($loud)
    {
        $this->addCall(__FUNCTION__);
    }

    public function SetMute($mute)
    {
        $this->addCall(__FUNCTION__);
    }

    public function SetNightMode($nightMode)
    {
        $this->addCall(__FUNCTION__);
    }

    public function SetPlayMode($PlayMode)
    {
        $this->addCall(__FUNCTION__);
    }

    public function SetQueue($queue)
    {
        $this->addCall(__FUNCTION__);
    }

    public function SetRadio($radio, $radio_name = 'IP-Symcon Radio')
    {
        $this->addCall(__FUNCTION__);
    }

    public function SetSleeptimer($hours, $minutes, $seconds)
    {
        $this->addCall(__FUNCTION__);
    }

    public function SetTrack($track)
    {
        $this->addCall(__FUNCTION__);
    }

    public function SetTreble($treble)
    {
        $this->addCall(__FUNCTION__);
    }

    public function SetVolume($volume, $channel = 'Master')
    {
        $this->addCall(__FUNCTION__);
    }

    public function Stop()
    {
        $this->addCall(__FUNCTION__);
    }

    public function GetCalls(): array
    {
        return $this->calls;
    }

    public function SetResponse(array $response)
    {
        $this->return = $response;
    }

    public function SetIP(string $ip)
    {
        $this->IP = $ip;
    }

    private function getReturn(string $function)
    {
        if (isset($this->return[$function])) {
            $return = array_shift($this->return[$function]);
            if (count($this->return[$function]) === 0) {
                unset($this->return[$function]);
            }
        } else {
            throw new Exception('Call of function "' . $function . '" was not expected');
        }
        return $return;
    }
    private function addCall(string $function)
    {
        if (!isset($this->calls[$function])) {
            $this->calls[$function] = [$this->IP => 1];
        } else {
            if (!isset($this->calls[$function][$this->IP])) {
                $this->calls[$function][$this->IP] = 1;
            } else {
                $this->calls[$function][$this->IP] += 1;
            }
        }
    }
}