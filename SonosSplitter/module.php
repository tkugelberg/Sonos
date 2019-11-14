<?php

require_once __DIR__ . '/../libs/sonosAccess.php'; // SOAP Access to Sonos
require_once __DIR__ . '/../libs/VariableProfile.php';
require_once __DIR__ . '/../libs/CommonFunctions.php';

class SonosSplitter extends IPSModule
{

  use VariableProfile,
    CommonFunctions;

  public function Create()
  {
    // Diese Zeile nicht löschen.

    $radioStationDefault = json_encode([
      ['name' => 'SWR3',             'URL' => 'x-rincon-mp3radio://mp3-live.swr3.de/swr3_m.m3u',                      'imageURL' => 'http://cdn-radiotime-logos.tunein.com/s24896q.png'],
      ['name' => 'AC/DC Collection', 'URL' => 'x-rincon-mp3radio://streams.radiobob.de/bob-acdc/mp3-192/mediaplayer', 'imageURL' => 'http://cdn-radiotime-logos.tunein.com/s256712.png'],
      ['name' => 'FFN',              'URL' => 'x-rincon-mp3radio://player.ffn.de/ffn.mp3',                            'imageURL' => 'http://cdn-radiotime-logos.tunein.com/s8954q.png']
    ]);

    parent::Create();
    $this->RegisterPropertyInteger("PlaylistImport", 0);
    $this->RegisterPropertyInteger("AlbumArtHeight", 170);
    $this->RegisterPropertyString("RadioStations", $radioStationDefault);
    $this->RegisterPropertyInteger("UpdateGroupingFrequency", 120);
    $this->RegisterPropertyInteger("UpdateStatusFrequency", 5);
    $this->RegisterTimer('Sonos Update Grouping', 0, 'SNS_updateGrouping(' . $this->InstanceID . ');');
  } // End Create

  public function ApplyChanges()
  {
    $this->RegisterMessage(0, IPS_KERNELSTARTED);
    // Diese Zeile nicht löschen
    parent::ApplyChanges();

    // create profiles
    $this->RegisterProfileIntegerEx("SONOS.Status", "Information", "", "",   array(
      array(0, "prev",       "", -1),
      array(1, "play",       "", -1),
      array(2, "pause",      "", -1),
      array(3, "stop",       "", -1),
      array(4, "next",       "", -1),
      array(5, "transition", "", -1)
    ));
    $this->RegisterProfileIntegerEx("SONOS.PlayMode", "Information", "", "",   array(
      array(0, "Normal",             "", -1),
      array(1, "Repeat all",         "", -1),
      array(2, "Repeat one",         "", -1),
      array(3, "Shuffle no repeat",  "", -1),
      array(4, "Shuffle",            "", -1),
      array(5, "Shuffle repeat one", "", -1)
    ));
    $this->RegisterProfileInteger("SONOS.Volume",   "Intensity",   "", " %",    0, 100, 1);
    $this->RegisterProfileInteger("SONOS.Tone",     "Intensity",   "", " %",  -10,  10, 1);
    $this->RegisterProfileInteger("SONOS.Balance",  "Intensity",   "", " %", -100, 100, 1);
    $this->RegisterProfileIntegerEx("SONOS.Switch", "Information", "",   "", array(
      array(0, $this->Translate("Off"), "", 0xFF0000),
      array(1, $this->Translate("On"),  "", 0x00FF00)
    ));

    // Radio Stations
    $radioStations = json_decode($this->ReadPropertyString("RadioStations"), true);
    $Associations  = [];
    $Value         = 1;

    foreach ($radioStations as $radioStation) {
      $Associations[] = array($Value++, $radioStation['name'], "", -1);
      // associations only support up to 32 variables
      if ($Value === 33) break;
    }

    if (IPS_VariableProfileExists("SONOS.Radio")) IPS_DeleteVariableProfile("SONOS.Radio");
    $this->RegisterProfileIntegerEx("SONOS.Radio", "Speaker", "", "", $Associations);

    $this->UpdatePlaylists();
    $this->updateGroupingProfile();

    $this->SetTimerInterval('Sonos Update Grouping', $this->ReadPropertyInteger('UpdateGroupingFrequency') * 1000);
    // Send different propeties to player instances
    $this->SendDataToChildren(json_encode([
      "DataID" => '{36EA4430-7047-C11D-0854-43391B14E0D7}',
      'type'   => 'updateStatus',
      'data'   => $this->ReadPropertyInteger('UpdateStatusFrequency')
    ]));
    $this->SendDataToChildren(json_encode([
      "DataID" => '{36EA4430-7047-C11D-0854-43391B14E0D7}',
      'type'   => 'RadioStations',
      'data'   => $this->ReadPropertyString('RadioStations')
    ]));
    $this->SendDataToChildren(json_encode([
      "DataID" => '{36EA4430-7047-C11D-0854-43391B14E0D7}',
      'type'   => 'AlbumArtHight',
      'data'   => $this->ReadPropertyInteger('AlbumArtHeight')
    ]));
  } // End ApplyChanges


  public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
  {
    switch ($Message) {
      case IPS_KERNELSTARTED:
        // Set Timer for update Status in all Player instances
        $this->SendDataToChildren(json_encode([
          "DataID" => '{36EA4430-7047-C11D-0854-43391B14E0D7}',
          'type'   => 'updateStatus',
          'data'   => $this->ReadPropertyInteger('UpdateStatusFrequency')
        ]));
        break;
    }
  }

  public function ForwardData($JSONString)
  {
    $input = json_decode($JSONString, true);
    switch ($input['type']) {
      case 'AlbumArtRequest':
        $this->SendDataToChildren(json_encode([
          "DataID" => '{36EA4430-7047-C11D-0854-43391B14E0D7}',
          'type'   => 'AlbumArtHight',
          'data'   => $this->ReadPropertyInteger('AlbumArtHeight')
        ]));
        break;
      case 'UpdateStatusFrequencyRequest':
        $this->SendDataToChildren(json_encode([
          "DataID" => '{36EA4430-7047-C11D-0854-43391B14E0D7}',
          'type'   => 'updateStatus',
          'data'   => $this->ReadPropertyInteger('UpdateStatusFrequency')
        ]));
        break;
      case 'RadioStationsRequest':
        $this->SendDataToChildren(json_encode([
          "DataID" => '{36EA4430-7047-C11D-0854-43391B14E0D7}',
          'type'   => 'RadioStations',
          'data'   => $this->ReadPropertyString('RadioStations')
        ]));
        break;
      default:
        throw new Exception($this->Translate("unknown type in ForwardData"));
    }
  }

  public function GetConfigurationForm()
  {

    $Form   = [
      'elements' => [
        [
          'name'     => 'AlbumArtHeight',
          'type'     => 'NumberSpinner',
          'caption'  => 'Album Art Hight in WebFront',
          'suffix'   => 'px'
        ],
        [
          'name'     => 'UpdateGroupingFrequency',
          'type'     => 'NumberSpinner',
          'caption'  => 'Update Grouping Frequency',
          'suffix'   => 's'
        ],
        [
          'name'     => 'UpdateStatusFrequency',
          'type'     => 'NumberSpinner',
          'caption'  => 'Update Status Frequency',
          'suffix'   => 's'
        ],
        [
          'type' => 'RowLayout', 'items' => [
            [
              'name'     => 'PlaylistImport',
              'type'     => 'Select',
              'caption'  => 'Import Playlists',
              'options'  => [
                ['caption' => 'none',                        'value' => 0],
                ['caption' => 'saved',                       'value' => 1],
                ['caption' => 'imported',                    'value' => 2],
                ['caption' => 'saved & imported',            'value' => 3],
                ['caption' => 'favorites',                   'value' => 4],
                ['caption' => 'saved & favorites',           'value' => 5],
                ['caption' => 'imported & favorites',        'value' => 6],
                ['caption' => 'saved, imported & favorites', 'value' => 7]
              ]
            ],
            [
              'name' => 'updatePlaylist',
              'type' => 'Button',
              'caption' => 'update playlists',
              'onClick'  => 'SNS_UpdatePlaylists($id);'
            ]
          ]
        ],
        [
          'name'     => 'RadioStations',
          'type'     => 'List',
          'caption'  => 'Radio Stations',
          'rowCount' => 6,
          'add'      => true,
          'delete'   => true,
          'columns'  => [
            [
              'caption' => 'Name',
              'name'    => 'name',
              'width'   => '150px',
              'edit'    => ['type' => 'ValidationTextBox'],
              'add'     => '',
              'save'    => true
            ],
            [
              'caption' => 'URL',
              'name'    => 'URL',
              'width'   => '450px',
              'edit'    => ['type' => 'ValidationTextBox'],
              'add'     => '',
              'save'    => true
            ],
            [
              'caption' => 'Image URL',
              'name'    => 'imageURL',
              'width'   => '450px',
              'edit'    => ['type' => 'ValidationTextBox'],
              'add'     => '',
              'save'    => true
            ]
          ]
        ]
      ],
      'actions' => [['name' => 'readTunein', 'type' => 'Button', 'caption' => 'read Tunein', 'onClick'  => 'SNS_ReadTunein($id);']]
    ];
    return json_encode($Form);
  } // End GetConfigurationForm

  public function updateGrouping()
  {
    // get all Player instances, including required data
    $InstanceIDList = IPS_GetInstanceListByModuleID('{52F6586D-A1C7-AAC6-309B-E12A70F6EEF6}');
    $InstanceList   = [];
    foreach ($InstanceIDList as $InstanceID) {
      $InstanceList[IPS_GetProperty($InstanceID, "RINCON")] = [
        "IPAddress"     => IPS_GetProperty($InstanceID, "IPAddress"),
        "TimeOut"       => 500,
        "InstanceID"    => $InstanceID,
      ];
    }

    // Get grouping info from one of these Instances
    foreach ($InstanceList as $RINCON => $Instance) {
      if (@Sys_Ping($Instance["IPAddress"], $Instance["TimeOut"]) == true) {

        $sonos    = new SonosAccess($Instance["IPAddress"]);
        $SonosGrouping = new SimpleXMLElement($sonos->GetZoneGroupState());
        // Some of my speakers moved ZoneGroup to ZoneGroups->ZoneGroup what leads to an error - this should solve it
        if ($SonosGrouping) {
          break;
        }
      }
    }

    $Grouping = [];
    foreach ($SonosGrouping->ZoneGroups->ZoneGroup as $zoneGroup) {
      //get RINCON of Coordinator
      $CoordinatorRINCON = (string) $zoneGroup->attributes()['Coordinator'];
      // check if Coordinator is configured in Symcon
      if (isset($InstanceList[$CoordinatorRINCON])) {
        $CoordinatorInstanceID = $InstanceList[$CoordinatorRINCON]["InstanceID"];
      } else {
        $CoordinatorInstanceID = 0; // 0 means is not configured
      }
      $GroupMember = [];
      foreach ($zoneGroup->ZoneGroupMember as $zoneGroupMember) {
        // Instances marked as "Invisible" are things like Stereo Pairs or Bridge
        if (isset($zoneGroupMember->attributes()['Invisible'])) {
          continue;
        }
        // Get RINCON of Member
        $RINCON = (string) $zoneGroupMember->attributes()['UUID'];
        // Instance is not configured in Symcon --> ignore
        if (!isset($InstanceList[$RINCON])) {
          continue;
        }
        // Coordinator is also part of group --> do not add as own entry
        if ($RINCON != $CoordinatorRINCON) {
          $Grouping[$RINCON] = [
            'isCoordinator' => false,
            'vanished'      => false,
            'GroupMember'   => [],
            'Coordinator'   => $CoordinatorInstanceID
          ];
          $GroupMember[] = $InstanceList[$RINCON]["InstanceID"];
        }
      }

      // --> Instance not in Symcon
      if ($CoordinatorInstanceID == 0) {
        // But members are, worth an exception
        if (count($GroupMember) != 0) {
          throw new Exception($this->Translate("Coordinator is not configured as Symcon instance. This cannot work."));
        } else {   // also the members are not in Symcon, can be ignored
          continue;
        }
      }

      // Add Coordinator including all members
      $Grouping[$CoordinatorRINCON] = [
        'isCoordinator' => true,
        'vanished'      => false,
        'GroupMember'   => $GroupMember,
        'Coordinator'   => 0
      ];
    }

    foreach ($SonosGrouping->VanishedDevices->Device as $device) {
      $Grouping[strval($device->attributes()['UUID'])] = [
        'isCoordinator' => false,
        'vanished'      => true,
        'GroupMember'   => [],
        'Coordinator'   => 0
      ];
    }

    $this->SendDataToChildren(json_encode([
      "DataID" => '{36EA4430-7047-C11D-0854-43391B14E0D7}',
      'type'   => 'grouping',
      'data'   => $Grouping
    ]));
  } // End update Grouping

  public function ReadTunein()
  {
    // get all Player instances, including IP/Host and InstanceID
    $InstanceIDList = IPS_GetInstanceListByModuleID('{52F6586D-A1C7-AAC6-309B-E12A70F6EEF6}');
    $InstanceList   = [];
    foreach ($InstanceIDList as $InstanceID) {
      $InstanceList[$InstanceID] = [
        "IPAddress"  => IPS_GetProperty($InstanceID, "IPAddress"),
        "TimeOut"    => IPS_GetProperty($InstanceID, "TimeOut"),
      ];
    }

    // Get grouping info from one of these Instances
    foreach ($InstanceList as $InstanceID => $Instance) {
      if (Sys_Ping($Instance["IPAddress"], $Instance["TimeOut"]) == true) {
        $sonos = new SonosAccess($Instance["IPAddress"]);
        break;
      }
    }

    if (!isset($sonos)) throw new Exception($this->Translate("Unable to access any Sonos Instance"));

    $tuneinStations = new SimpleXMLElement($sonos->BrowseContentDirectory('R:0/0')['Result']);

    $radioStations = json_decode($this->ReadPropertyString("RadioStations"), true);

    foreach ($tuneinStations as $tuneinStation) {
      $name = strval($tuneinStation->xpath('dc:title')[0]);
      $url  = strval($tuneinStation->res);
      preg_match('/s\d{3,}/', $url, $station);
      $imageurl = "http://cdn-radiotime-logos.tunein.com/" . $station[0] . "q.png";
      $alreadyIn = false;
      foreach ($radioStations as $radioStation) {
        if ($radioStation['name'] == $name &&  $radioStation['URL'] == $url && $radioStation['imageURL'] == $imageurl) {
          $alreadyIn = true;
          break;
        }
      }
      if ($alreadyIn == false) {
        $radioStations[] = [
          'name' => $name,
          'URL'  => $url,
          'imageURL' =>  $imageurl
        ];
      }
    }

    $this->UpdateFormField('RadioStations', 'values', json_encode($radioStations));
  }

  public function UpdatePlaylists()
  {

    $Associations          = array();
    $PlaylistImport        = $this->ReadPropertyInteger("PlaylistImport");

    if ($PlaylistImport != 0) {
      // get all Player instances, including IP/Host and InstanceID
      $InstanceIDList = IPS_GetInstanceListByModuleID('{52F6586D-A1C7-AAC6-309B-E12A70F6EEF6}');
      $InstanceList   = [];
      foreach ($InstanceIDList as $InstanceID) {
        $InstanceList[$InstanceID] = [
          "IPAddress"  => IPS_GetProperty($InstanceID, "IPAddress"),
          "TimeOut"    => IPS_GetProperty($InstanceID, "TimeOut"),
        ];
      }

      // Get grouping info from one of these Instances
      foreach ($InstanceList as $InstanceID => $Instance) {
        if (Sys_Ping($Instance["IPAddress"], $Instance["TimeOut"]) == true) {
          $sonos = new SonosAccess($Instance["IPAddress"]);
          break;
        }
      }

      if (!isset($sonos)) throw new Exception($this->Translate("Unable to access any Sonos Instance"));

      $Value = 1;

      // saved
      if ($PlaylistImport === 1 || $PlaylistImport === 3 || $PlaylistImport === 5  || $PlaylistImport === 7) {
        foreach ((new SimpleXMLElement($sonos->BrowseContentDirectory('SQ:')['Result']))->container as $container) {
          $Associations[] = array($Value++, (string) $container->xpath('dc:title')[0], "", -1);
          // associations only support up to 32 variables
          if ($Value === 33) break;
        }
      }

      // imported
      if (($PlaylistImport === 2 || $PlaylistImport === 3 || $PlaylistImport === 6  || $PlaylistImport === 7) && $Value < 33) {
        foreach ((new SimpleXMLElement($sonos->BrowseContentDirectory('A:PLAYLISTS')['Result']))->container as $container) {
          $Associations[] = array($Value++, (string) preg_replace($this->getPlaylistReplacementFrom(), $this->getPlaylistReplacementTo(), $container->xpath('dc:title')[0]), "", -1);
          // associations only support up to 32 variables
          if ($Value === 33) break;
        }
      }

      // favorites
      if (($PlaylistImport === 4 || $PlaylistImport === 5 || $PlaylistImport === 6 || $PlaylistImport === 7) && $Value < 33) // Spotify Playlist saved as Sonos Favorite
      {
        foreach ((new SimpleXMLElement($sonos->BrowseContentDirectory('FV:2')['Result']))->item as $item) {
          $Associations[] = array($Value++, (string) preg_replace($this->getPlaylistReplacementFrom(), $this->getPlaylistReplacementTo(), $item->xpath('dc:title')[0]), "", -1);
          // associations only support up to 32 variables
          if ($Value === 33) break;
        }
      }
    }

    if (IPS_VariableProfileExists("SONOS.Playlist"))
      IPS_DeleteVariableProfile("SONOS.Playlist");

    $this->RegisterProfileIntegerEx("SONOS.Playlist", "Database", "", "", $Associations);
  } // End UpdatePlaylists


  private function updateGroupingProfile()
  {
    if (IPS_VariableProfileExists("SONOS.Groups")) IPS_DeleteVariableProfile("SONOS.Groups");
    $allSonosPlayers = IPS_GetInstanceListByModuleID("{52F6586D-A1C7-AAC6-309B-E12A70F6EEF6}");
    $GroupAssociations = array(array(0, "none", "", -1));

    foreach ($allSonosPlayers as $InstanceID) {
      if (@GetValueBoolean(IPS_GetVariableIDByName("Coordinator", $InstanceID)))
        $GroupAssociations[] = array($InstanceID, IPS_GetName($InstanceID), "", -1);
    }

    $this->RegisterProfileIntegerEx("SONOS.Groups", "Network", "", "", $GroupAssociations);
  }
}
