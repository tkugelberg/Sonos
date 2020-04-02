<?php

declare(strict_types=1);

class SonosAccessDouble
{
    private $calls = [];

    public function AddToQueue($file, $meta = '')
    {
    }

    public function BrowseContentDirectory($objectID = 'SQ:', $browseFlag = 'BrowseDirectChildren', $requestedCount = 100, $startingIndex = 0, $filter = '', $sortCriteria = ''): array
    {
    }

    public function ClearQueue()
    {
    }

    public function DelegateGroupCoordinationTo(string $NewCoordinator, bool $RejoinGroup)
    {
    }

    public function GetBass(): int
    {
    }

    public function GetCrossfade(): bool
    {
    }

    public function GetDialogLevel(): bool
    {
    }

    public function GetLoudness(): bool
    {
    }

    public function GetMediaInfo(): array
    {
    }

    public function GetMute(): bool
    {
    }

    public function GetNightMode(): bool
    {
    }

    public function GetOutputFixed(): bool
    {
    }

    public function GetPositionInfo(): array
    {
    }

    public function GetSleeptimer(): string
    {
    }

    public function GetTransportInfo(): int
    {
    }

    public function GetTransportSettings(): int
    {
    }

    public function GetTreble(): int
    {
    }

    public function GetVolume($channel = 'Master'): int
    {
    }

    public function GetZoneGroupAttributes(): array
    {
    }

    public function GetZoneGroupState(): string
    {
    }

    public function Next()
    {
    }

    public function Pause()
    {
    }

    public function Play()
    {
        if (!isset($this->calls['Play'])) {
            $this->calls['Play'] = 1;
        } else {
            $this->calls['Play'] += 1;
        }
    }

    public function Previous()
    {
    }

    public function RampToVolume($rampType, $volume)
    {
    }

    public function RemoveFromQueue($track)
    {
    }

    public function Rewind()
    {
    }

    public function Seek($unit, $target)
    {
    }

    public function SetAVTransportURI($tspuri, $MetaData = '')
    {
    }

    public function SetBass($bass)
    {
    }

    public function SetCrossfade($crossfade)
    {
    }

    public function SetDialogLevel($dialogLevel)
    {
    }

    public function SetLoudness($loud)
    {
    }

    public function SetMute($mute)
    {
    }

    public function SetNightMode($nightMode)
    {
    }

    public function SetPlayMode($PlayMode)
    {
    }

    public function SetQueue($queue)
    {
    }

    public function SetRadio($radio, $radio_name = 'IP-Symcon Radio')
    {
    }

    public function SetSleeptimer($hours, $minutes, $seconds)
    {
    }

    public function SetTrack($track)
    {
    }

    public function SetTreble($treble)
    {
    }

    public function SetVolume($volume, $channel = 'Master')
    {
    }

    public function Stop()
    {
    }

    public function GetCalls(): array
    {
        return $this->calls;
    }
}