<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

include_once __DIR__ . '/stubs/GlobalStubs.php';
include_once __DIR__ . '/stubs/KernelStubs.php';
include_once __DIR__ . '/stubs/ModuleStubs.php';
include_once __DIR__ . '/stubs/MessageStubs.php';

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
    }

    public function testDummy()
    {
        $this->assertEquals(1, 1);
    }

    /*
    public function testPlay()
    {
        $playerID = IPS_CreateInstance($this->playerModulID);

        $playerInterface = IPS\InstanceManager::getInstanceInterface($playerID);

        IPS_CreateVariableProfile('~HTMLBox', 3);

        IPS_SetProperty($playerID, 'IPAddress', '192.168.1.2');
        IPS_SetProperty($playerID, 'SleeptimerControl', true );
        IPS_SetProperty($playerID, 'PlayModeControl', true );
        IPS_SetProperty($playerID, 'DetailedInformation', true );
        IPS_ApplyChanges($playerID);

        $playerInterface->ReceiveData(json_encode([
            'DataID'         => '{36EA4430-7047-C11D-0854-43391B14E0D7}',
            'type'           => 'grouping',
            'targetInstance' => $playerID,
            'data'           => ['isCoordinator' => true,
                                 'vanished'      => false,
                                 'GroupMember'   => [],
                                 'Coordinator'   => 0 ]
        ]));

        $playerInterface->Play($ModulID);
    }
     */
}
