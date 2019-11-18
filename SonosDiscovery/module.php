<?php

require_once __DIR__ . '/../libs/sonosAccess.php'; // SOAP Access to Sonos

class SonosDiscovery extends ipsmodule
{

  public function Create()
  {
    parent::Create();
    $this->Devices = [];
    $this->RegisterTimer('Sonos Discovery', 0, 'SNS_Discover(' . $this->InstanceID . ');');
  }

  public function ApplyChanges()
  {
    $this->RegisterMessage(0, IPS_KERNELSTARTED);
    parent::ApplyChanges();
    $this->SetTimerInterval('Sonos Discovery', 300000);
    if (IPS_GetKernelRunlevel() != KR_READY) {
      return;
    }
    $this->Devices = $this->DiscoverDevices();
  }

  public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
  {
    switch ($Message) {
      case IPS_KERNELSTARTED:
        $this->Devices = $this->DiscoverDevices();
        break;
    }
  }

  public function Discover()
  {
    $this->Devices = $this->DiscoverDevices();
  }

  public function GetConfigurationForm()
  {
    $SonosDevices = $this->DiscoverDevices();
    $IPSSonosDevices = $this->GetIPSInstances();

    $Values = [];

    foreach ($SonosDevices as $RINCON => $SonosDevice) {
      $InstanceID = array_search($RINCON, $IPSSonosDevices);
      $AddValue = [
        'name'       => $SonosDevice['Name'],
        'IPAddress'  => $SonosDevice['IPAddress'],
        'Type'       => $SonosDevice['Type'],
        'RINCON'     => $SonosDevice['RINCON'],
        'instanceID' => 0
      ];
      if ($InstanceID !== false) {
        unset($IPSSonosDevices[$InstanceID]);
        $AddValue['name'] = IPS_GetLocation($InstanceID);
        $AddValue['instanceID'] = $InstanceID;
      }
      $AddValue['create'] = [
        [
          'moduleID'        => '{52F6586D-A1C7-AAC6-309B-E12A70F6EEF6}',
          'configuration'   => [
            'IPAddress' => $SonosDevice['IPAddress'],
            'RINCON'    => $SonosDevice['RINCON']
          ]
        ],
        [
          'moduleID'        => '{27B601A0-6EA4-89E3-27AD-2D902307BD8C}',
          'configuration'   => []
        ]
      ];
      $Values[] = $AddValue;
    }

    foreach ($IPSSonosDevices as $InstanceID => $RINCON) {
      $Values[] = [
        'name'       => IPS_GetLocation($InstanceID),
        'IPAddress'  => IPS_GetProperty($InstanceID, "IPAddress"),
        'Type'       => '',
        'RINCON'     => $RINCON,
        'instanceID' => $InstanceID
      ];
    }

    $Form   = ['actions' => [[
      'type'    => 'Configurator',
      'name'    => 'Discovery',
      'add'     =>  false,
      'create'  =>  true,
      'sort'    => [
        'column'    => 'name',
        'direction' => 'ascending'
      ],
      'columns' => [
        ['caption' => 'Name',       'name' => 'name',      'width' => '200px'],
        ['caption' => 'IP Address', 'name' => 'IPAddress', 'width' => '160px'],
        ['caption' => 'Type',       'name' => 'Type',      'width' => '100px'],
        ['caption' => 'RINCON',     'name' => 'RINCON',    'width' => '250px']
      ],
      'values'  => $Values
    ]]];
    return json_encode($Form);
  }

  /**
   *  Internal Functions
   */
  private function GetIPSInstances(): array
  {
    $InstanceIDList = IPS_GetInstanceListByModuleID('{52F6586D-A1C7-AAC6-309B-E12A70F6EEF6}');
    $SonosDevices = [];
    foreach ($InstanceIDList as $InstanceID) {
      $SonosDevices[$InstanceID] = IPS_GetProperty($InstanceID, "RINCON");
    }
    return $SonosDevices;
  }

  private function DiscoverDevices(): array
  {
    $SonosDevices = [];

    $msg  = 'M-SEARCH * HTTP/1.1' . "\r\n";
    $msg .= 'HOST: 239.255.255.250:1900' . "\r\n";
    $msg .= 'MAN: "ssdp:discover"' . "\r\n";
    $msg .= "MX: 3\r\n";
    $msg .= "ST: urn:schemas-upnp-org:device:ZonePlayer:1\r\n";
    $msg .= '' . "\r\n";

    $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1);
    socket_sendto($socket, $msg, strlen($msg), 0, '239.255.255.250', 1900);
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 2, 'usec' => 0));

    $res = null;
    @socket_recvfrom($socket, $res, 1024, 0, $from, $port);

    if (!is_null($res)) {
      $lines  = explode("\n", trim($res));

      if (trim($lines[0]) == 'HTTP/1.1 200 OK') {
        array_shift($lines);
      }

      foreach ($lines as $line) {
        $tmp = explode(':', trim($line));
        if (strtoupper(array_shift($tmp)) === 'LOCATION') {
          $value = (count($tmp) > 0 ? trim(join(':', $tmp)) : null);
          if (preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $value, $ip_match)) {
            if (Sys_ping($ip_match[0], 1000) == true) {
              $ip = $ip_match[0];
              break;
            }
          }
        }
      }
    }

    if (!isset($ip)) {
      return $SonosDevices;
    }

    $sonos = new SonosAccess($ip);

    $grouping = new SimpleXMLElement($sonos->GetZoneGroupState());
    $zoneGroups = $grouping->ZoneGroups->ZoneGroup;

    foreach ($zoneGroups as $zoneGroup) {
      foreach ($zoneGroup->ZoneGroupMember as $zoneGroupMember) {
        if (!isset($zoneGroupMember->attributes()['Invisible'])) {
          if (preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $zoneGroupMember->attributes()['Location'], $ip_match)) {
            $description = new SimpleXMLElement($zoneGroupMember->attributes()['Location'], 0, true);
            $SonosDevices[strval($zoneGroupMember->attributes()['UUID'])] = [
              'Name'      => strval($zoneGroupMember->attributes()['ZoneName']),
              'Type'      => strval($description->device->displayName),
              'RINCON'    => strval($zoneGroupMember->attributes()['UUID']),
              'IPAddress' => $ip_match[0]
            ];
          }
        }
      }
    }
    return $SonosDevices;
  } // End DiscoverDevices
}
