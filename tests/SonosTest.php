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

    public function testIsCoordinator()
    {
        $players = [
            'coordinator'   => [
                'IP'             => '192.168.1.2',
                'isCoordinator'  => true,
                'vanished'       => false,
                'GroupMember'    => ['member'],
                'coordinator'    => ''
            ],
            'member' => [
                'IP'             => '192.168.1.3',
                'isCoordinator'  => false,
                'vanished'       => false,
                'GroupMember'    => [],
                'coordinator'    => 'coordinator'
            ]
        ];
        $this->createPlayers($players);
        
        
        $this->assertEquals(true, SNS_IsCoordinator($players['coordinator']['ID']));
        $this->assertEquals(false, SNS_IsCoordinator($players['member']['ID']));
    }

    public function testPlay()
    {
        $players = [
            0   => [
                'IP'             => '192.168.1.2',
                'isCoordinator'  => true,
                'vanished'       => false,
                'GroupMember'    => [],
                'coordinator'    => ''
            ]
        ];
        $this->createPlayers($players);

        $sonosDouble = new SonosAccessDouble();
        SNS_setSonos($players[0]['ID'], $sonosDouble);

        SNS_Play($players[0]['ID']);

        $this->assertEquals(['Play' => ['192.168.1.2' => 1]], $sonosDouble->GetCalls());
        $this->assertEquals(1, GetValueInteger(IPS_GetVariableIDByName('Status', $players[0]['ID'])));
    }

    public function testPlayForward()
    {
        $players = [
            'coordinator'   => [
                'IP'             => '192.168.1.2',
                'isCoordinator'  => true,
                'vanished'       => false,
                'GroupMember'    => ['member'],
                'coordinator'    => ''
            ],
            'member' => [
                'IP'             => '192.168.1.3',
                'isCoordinator'  => false,
                'vanished'       => false,
                'GroupMember'    => [],
                'coordinator'    => 'coordinator'
            ]
        ];
        $this->createPlayers($players);

        $sonosDouble = new SonosAccessDouble();
        SNS_setSonos($players['coordinator']['ID'], $sonosDouble);
        SNS_setSonos($players['member']['ID'], $sonosDouble);

        SNS_Play($players['member']['ID']);

        $this->assertEquals(['Play' => ['192.168.1.2' => 1]], $sonosDouble->GetCalls());
        $this->assertEquals(1, GetValueInteger(IPS_GetVariableIDByName('Status', $players['coordinator']['ID'])));
    }

    public function testPause()
    {
        $players = [
            0   => [
                'IP'             => '192.168.1.2',
                'isCoordinator'  => true,
                'vanished'       => false,
                'GroupMember'    => [],
                'coordinator'    => ''
            ]
        ];
        $this->createPlayers($players);

        $sonosDouble = new SonosAccessDouble();
        SNS_setSonos($players[0]['ID'], $sonosDouble);

        $sonosDouble->SetResponse(['GetTransportInfo' => [1, 2]]);

        SNS_Pause($players[0]['ID']);

        $this->assertEquals(['Pause' => ['192.168.1.2' => 1], 'GetTransportInfo' => ['192.168.1.2' => 1]], $sonosDouble->GetCalls());
        $this->assertEquals(2, GetValueInteger(IPS_GetVariableIDByName('Status', $players[0]['ID'])));

        SNS_Pause($players[0]['ID']);
        $this->assertEquals(['Pause' => ['192.168.1.2' => 1], 'GetTransportInfo' => ['192.168.1.2' => 2]], $sonosDouble->GetCalls());
        $this->assertEquals(2, GetValueInteger(IPS_GetVariableIDByName('Status', $players[0]['ID'])));
    }

    private function createPlayers(array &$players)
    {
        // create players
        foreach ($players as &$player) {
            $player['ID'] = IPS_CreateInstance($this->playerModulID);
            IPS_SetProperty($player['ID'], 'IPAddress', $player['IP']);
            IPS_SetProperty($player['ID'], 'DisableHiding', true);
            IPS_SetProperty($player['ID'], 'SleeptimerControl', true);
            IPS_SetProperty($player['ID'], 'PlayModeControl', true);
            IPS_SetProperty($player['ID'], 'DetailedInformation', true);
            IPS_ApplyChanges($player['ID']);
        }

        unset($player); // remove reference, or it will be overwritten in next loop

        foreach ($players as $player) {
            $playerInterface = IPS\InstanceManager::getInstanceInterface($player['ID']);

            if ($player['coordinator'] == '') {
                $coordinator = 0;
            } else {
                $coordinator = $players['coordinator']['ID'];
            }

            $GroupMember = [];
            foreach ($player['GroupMember'] as $member) {
                $GroupMember[] = $players[$member]['ID'];
            }

            $playerInterface->ReceiveData(json_encode([
                'DataID'         => '{36EA4430-7047-C11D-0854-43391B14E0D7}',
                'type'           => 'grouping',
                'targetInstance' => $player['ID'],
                'data'           => [
                    'isCoordinator' => $player['isCoordinator'],
                    'vanished'      => $player['vanished'],
                    'GroupMember'   => $GroupMember,
                    'Coordinator'   => $coordinator
                ]
            ]));
        }
    }
}