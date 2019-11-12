<?php

trait CommonFunctions
{

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

  private function getPlaylistReplacementFrom()
  {
    return  array(
      '/\.m3u$/',
      '/\.M3U$/',
      '/_/'
    );
  }

  private function getPlaylistReplacementTo()
  {
    return  array(
      '',
      '',
      ' '
    );
  }
}
