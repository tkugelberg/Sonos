<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

include_once __DIR__ . '/stubs/GlobalStubs.php';
include_once __DIR__ . '/stubs/KernelStubs.php';
include_once __DIR__ . '/stubs/ModuleStubs.php';
include_once __DIR__ . '/stubs/MessageStubs.php';
include_once __DIR__ . '/sonosAccessDouble.php';

/*
Missing tests:
    public function ApplyChanges() --> all variables created?!
    public function GetConfigurationForm()
    public function ReceiveData($JSONstring)
    public function alexaResponse()
    public function ChangeGroupVolume(int $increment)
    public function ChangeVolume(int $increment)
    public function DelegateGroupCoordinationTo(int $newGroupCoordinator, bool $rejoinGroup)
    public function DeleteSleepTimer()
    public function Next()
    public function PlayFiles(string $files, string $volumeChange)
    public function PlayFilesGrouping(string $instances, string $files, string $volumeChange)
    public function Previous()
    public function RampToVolume(string $rampType, int $volume)
    public function SetAnalogInput(int $input_instance)
    public function SetBalance(int $balance)
    public function SetBass(int $bass)
    public function SetCrossfade(bool $crossfade)
    public function SetDefaultGroupVolume()
    public function SetDefaultVolume()
    public function SetDialogLevel(bool $dialogLevel)
    public function SetGroup(int $groupCoordinator)
    public function SetGroupVolume(int $volume)
    public function SetHdmiInput(int $input_instance)
    public function SetLoudness(bool $loudness)
    public function SetMute(bool $mute)
    public function SetMuteGroup(bool $mute)
    public function SetNightMode(bool $nightMode)
    public function SetPlaylist(string $name)
    public function SetPlayMode(int $playMode)
    public function SetRadio(string $radio)
    public function SetSleepTimer(int $minutes)
    public function SetSpdifInput(int $input_instance)
    public function SetTransportURI(string $uri)
    public function SetTreble(int $treble)
    public function SetVolume(int $volume)
    public function Stop()
    public function updateStatus()
    public function RequestAction($Ident, $Value)
    public function getRINCON(string $ip)
    public function getModel(string $ip)
 */

class SonosTest extends TestCase
{
    private $discoveryModulID = '{8EFB4FEB-32D6-CB96-EA58-32CD86260774}';
    private $splitterModulID = '{27B601A0-6EA4-89E3-27AD-2D902307BD8C}';
    private $playerModulID = '{52F6586D-A1C7-AAC6-309B-E12A70F6EEF6}';

    public function setUp(): void
    {
        //Reset
        IPS\Kernel::reset();
        //Register our library we need for testing
        IPS\ModuleLoader::loadLibrary(__DIR__ . '/../library.json');
        parent::setUp();

        IPS_CreateVariableProfile('~HTMLBox', 3); // needed for Details
    }

    public function testPlay()
    {
        $playerID = $this->createPlayer('192.168.1.2');

        $sonosDouble = new SonosAccessDouble();
        SNS_setSonos($playerID, $sonosDouble);

        SNS_Play($playerID);

        $this->assertEquals(['Play' => 1], $sonosDouble->GetCalls());
        $this->assertEquals(1, GetValueInteger(IPS_GetVariableIDByName('Status', $playerID)));
    }

    public function testPause()
    {
        $playerID = $this->createPlayer('192.168.1.2');
        $sonosDouble = new SonosAccessDouble();
        SNS_setSonos($playerID, $sonosDouble);

        $sonosDouble->SetResponse(['GetTransportInfo' => [1, 2]]);

        SNS_Pause($playerID);

        $this->assertEquals(['Pause' => 1, 'GetTransportInfo' => 1], $sonosDouble->GetCalls());
        $this->assertEquals(2, GetValueInteger(IPS_GetVariableIDByName('Status', $playerID)));

        SNS_Pause($playerID);
        $this->assertEquals(['Pause' => 1, 'GetTransportInfo' => 2], $sonosDouble->GetCalls());
        $this->assertEquals(2, GetValueInteger(IPS_GetVariableIDByName('Status', $playerID)));
    }

    private function createPlayer(string $ip)
    {
        $playerID = IPS_CreateInstance($this->playerModulID);

        $playerInterface = IPS\InstanceManager::getInstanceInterface($playerID);

        IPS_SetProperty($playerID, 'IPAddress', $ip);
        IPS_SetProperty($playerID, 'DisableHiding', true);
        IPS_SetProperty($playerID, 'SleeptimerControl', true);
        IPS_SetProperty($playerID, 'PlayModeControl', true);
        IPS_SetProperty($playerID, 'DetailedInformation', true);
        IPS_ApplyChanges($playerID);

        $playerInterface->ReceiveData(json_encode([
            'DataID'         => '{36EA4430-7047-C11D-0854-43391B14E0D7}',
            'type'           => 'grouping',
            'targetInstance' => $playerID,
            'data'           => [
                'isCoordinator' => true,
                'vanished'      => false,
                'GroupMember'   => [],
                'Coordinator'   => 0
            ]
        ]));

        return $playerID;
    }
}