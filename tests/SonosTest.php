<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

include_once __DIR__ . '/stubs/GlobalStubs.php';
include_once __DIR__ . '/stubs/KernelStubs.php';
include_once __DIR__ . '/stubs/ModuleStubs.php';
include_once __DIR__ . '/stubs/MessageStubs.php';
include_once __DIR__ . '/stubs/ConstantStubs.php';
include_once __DIR__ . '/sonosAccessDouble.php';

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
        IPS_CreateVariableProfile('~PlaybackPreviousNext', 1); // needed for status
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
        $sonosDouble = new SonosAccessDouble();
        $this->createPlayers($players, $sonosDouble);

        $this->assertEquals(true, SNS_IsCoordinator($players['coordinator']['ID']));
        $this->assertEquals(false, SNS_IsCoordinator($players['member']['ID']));

        $this->assertEquals([], $sonosDouble->GetCalls());
    }

    public function testBecomeCoordinator()
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
        $sonosDouble = new SonosAccessDouble();
        $this->createPlayers($players, $sonosDouble);

        SNS_BecomeCoordinator($players['coordinator']['ID']);
        $this->assertEquals([], $sonosDouble->GetCalls());
        $this->assertEquals(true, SNS_IsCoordinator($players['coordinator']['ID'])); // still is coordinator
        $this->assertEquals(false, SNS_IsCoordinator($players['member']['ID']));       // still is member

        SNS_BecomeCoordinator($players['member']['ID']);
        $this->assertEquals(['DelegateGroupCoordinationTo' => ['192.168.1.2' => 1]], $sonosDouble->GetCalls());
        $this->assertEquals(false, SNS_IsCoordinator($players['coordinator']['ID'])); // is now a member
        $this->assertEquals(true, SNS_IsCoordinator($players['member']['ID']));       // is now coordinator
        $this->assertEquals($players['member']['ID'], GetValueInteger(IPS_GetObjectIDByIdent('MemberOfGroup', $players['coordinator']['ID']))); // Member of group has changed
        $this->assertEquals(0, GetValueInteger(IPS_GetObjectIDByIdent('MemberOfGroup', $players['member']['ID']))); // Member of group has changed
        // asserts for changed variables, etc.
    }

    public function testChangeVolume()
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
        $sonosDouble = new SonosAccessDouble();
        $this->createPlayers($players, $sonosDouble);
        SetValueInteger(IPS_GetVariableIDByName('Volume', $players[0]['ID']), 10);
        $this->assertEquals(10, GetValueInteger(IPS_GetVariableIDByName('Volume', $players[0]['ID'])));

        SNS_ChangeVolume($players[0]['ID'], 4);
        $this->assertEquals(14, GetValueInteger(IPS_GetVariableIDByName('Volume', $players[0]['ID'])));

        SNS_ChangeVolume($players[0]['ID'], 200);
        $this->assertEquals(100, GetValueInteger(IPS_GetVariableIDByName('Volume', $players[0]['ID'])));

        SNS_ChangeVolume($players[0]['ID'], -200);
        $this->assertEquals(0, GetValueInteger(IPS_GetVariableIDByName('Volume', $players[0]['ID'])));

        $sonosDouble->SetRaiseException(true);
        $exceptionText = '';
        try {
            SNS_ChangeVolume($players[0]['ID'], 3);
        } catch (Exception $e) {
            $exceptionText = $e->getMessage();
        }

        $this->assertEquals('UnitTest Exception SetVolume', $exceptionText);
        $this->assertEquals(0, GetValueInteger(IPS_GetVariableIDByName('Volume', $players[0]['ID'])));

        $this->assertEquals(['SetVolume' => ['192.168.1.2' => 4]], $sonosDouble->GetCalls());
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
        $sonosDouble = new SonosAccessDouble();
        $this->createPlayers($players, $sonosDouble);

        SNS_Play($players[0]['ID']);

        $this->assertEquals(['Play' => ['192.168.1.2' => 1]], $sonosDouble->GetCalls());
        $this->assertEquals(2, GetValueInteger(IPS_GetVariableIDByName('Status', $players[0]['ID'])));
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
        $sonosDouble = new SonosAccessDouble();
        $this->createPlayers($players, $sonosDouble);

        SNS_Play($players['member']['ID']);

        $this->assertEquals(['Play' => ['192.168.1.2' => 1]], $sonosDouble->GetCalls());
        $this->assertEquals(2, GetValueInteger(IPS_GetVariableIDByName('Status', $players['coordinator']['ID'])));
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

        $sonosDouble = new SonosAccessDouble();
        $this->createPlayers($players, $sonosDouble);

        $sonosDouble->SetResponse(['GetTransportInfo' => [2, 3]]);

        SNS_Pause($players[0]['ID']);

        $this->assertEquals(['Pause' => ['192.168.1.2' => 1], 'GetTransportInfo' => ['192.168.1.2' => 1]], $sonosDouble->GetCalls());
        $this->assertEquals(3, GetValueInteger(IPS_GetVariableIDByName('Status', $players[0]['ID'])));

        SNS_Pause($players[0]['ID']);
        $this->assertEquals(['Pause' => ['192.168.1.2' => 1], 'GetTransportInfo' => ['192.168.1.2' => 2]], $sonosDouble->GetCalls());
        $this->assertEquals(3, GetValueInteger(IPS_GetVariableIDByName('Status', $players[0]['ID'])));
    }

    private function createPlayers(array &$players, object $sonosDouble)
    {
        // create players
        foreach ($players as &$player) {
            $player['ID'] = IPS_CreateInstance($this->playerModulID);
            IPS_SetProperty($player['ID'], 'IPAddress', $player['IP']);
            if (isset($player['properties'])) {
                foreach ($player['properties'] as $propertyName => $propertyValue) {
                    IPS_SetProperty($player['ID'], $propertyName, $propertyValue);
                }
            }
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

            SNS_setSonos($player['ID'], $sonosDouble);
        }
    }
}
