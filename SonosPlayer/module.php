<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/sonosAccess.php'; // SOAP Access to Sonos
require_once __DIR__ . '/../libs/VariableProfile.php';
require_once __DIR__ . '/../libs/CommonFunctions.php';

class SonosPlayer extends IPSModule
{
    use VariableProfile;
    use CommonFunctions;

    public function Create()
    {
        // Diese Zeile nicht löschen.
        parent::Create();

        $this->ConnectParent('{27B601A0-6EA4-89E3-27AD-2D902307BD8C}'); // Connect To Splitter
        $this->SetReceiveDataFilter('.*"targetInstance":(' . $this->InstanceID . '|null).*');

        $this->RegisterPropertyString('IPAddress', '');
        $this->RegisterPropertyString('RINCON', '');
        $this->RegisterPropertyInteger('TimeOut', 1000);
        $this->RegisterPropertyInteger('DefaultVolume', 15);
        $this->RegisterPropertyBoolean('RejoinGroup', false);
        $this->RegisterPropertyBoolean('MuteControl', false);
        $this->RegisterPropertyBoolean('LoudnessControl', false);
        $this->RegisterPropertyBoolean('BassControl', false);
        $this->RegisterPropertyBoolean('TrebleControl', false);
        $this->RegisterPropertyBoolean('BalanceControl', false);
        $this->RegisterPropertyBoolean('SleeptimerControl', false);
        $this->RegisterPropertyBoolean('PlayModeControl', false);
        $this->RegisterPropertyBoolean('NightModeControl', false);
        $this->RegisterPropertyBoolean('DetailedInformation', false);
        $this->RegisterPropertyBoolean('ForceOrder', false);

        $this->RegisterAttributeBoolean('Coordinator', false);
        $this->RegisterAttributeString('GroupMembers', '');
        $this->RegisterAttributeBoolean('Vanished', false);

        // These Attributes will be configured on Splitter Instance and pushed down to Player Instances
        $this->RegisterAttributeInteger('AlbumArtHeight', -1);
        $this->RegisterAttributeString('RadioStations', '<undefined>');
        $this->RegisterAttributeInteger('UpdateStatusFrequency', -1);

        $this->RegisterTimer('Sonos Update Status', 0, 'SNS_updateStatus(' . $this->InstanceID . ');');
    }

    public function ApplyChanges()
    {
        // Diese Zeile nicht löschen
        parent::ApplyChanges();
        // 1) general availabe
        $positions = $this->getPositions();
        $this->RegisterVariableInteger('MemberOfGroup', $this->Translate('Member of group'), 'SONOS.Groups', $positions['MemberOfGroup']);
        $this->RegisterVariableInteger('GroupVolume', $this->Translate('Group volume'), 'SONOS.Volume', $positions['GroupVolume']);
        $this->RegisterVariableString('nowPlaying', $this->Translate('now Playing'), '', $positions['nowPlaying']);
        $this->RegisterVariableInteger('Radio', $this->Translate('Radio'), 'SONOS.Radio', $positions['Radio']);
        $this->RegisterVariableInteger('Status', $this->Translate('Status'), 'SONOS.Status', $positions['Status']);
        $this->RegisterVariableInteger('Volume', $this->Translate('Volume'), 'SONOS.Volume', $positions['Volume']);
        $this->RegisterVariableInteger('Playlist', $this->Translate('Playlist'), 'SONOS.Playlist', $positions['Playlist']);
        $this->EnableAction('GroupVolume');
        $this->EnableAction('MemberOfGroup');
        $this->EnableAction('Radio');
        $this->EnableAction('Status');
        $this->EnableAction('Volume');
        $this->checkPlaylistAction();

        // to be removed, these Variables moved to Attributes
        $this->removeVariable('Coordinator');
        $this->removeVariable('GroupMembers');

        // 2) Add/Remove according to feature activation

        // Bass
        if ($this->ReadPropertyBoolean('BassControl')) {
            $this->RegisterVariableInteger('Bass', $this->Translate('Bass'), 'SONOS.Tone', $positions['Bass']);
            $this->EnableAction('Bass');
        } else {
            $this->removeVariableAction('Bass');
        }

        // Treble
        if ($this->ReadPropertyBoolean('TrebleControl')) {
            $this->RegisterVariableInteger('Treble', $this->Translate('Treble'), 'SONOS.Tone', $positions['Treble']);
            $this->EnableAction('Treble');
        } else {
            $this->removeVariableAction('Treble');
        }

        // Mute
        if ($this->ReadPropertyBoolean('MuteControl')) {
            $this->RegisterVariableInteger('Mute', $this->Translate('Mute'), 'SONOS.Switch', $positions['Mute']);
            $this->EnableAction('Mute');
        } else {
            $this->removeVariableAction('Mute');
        }

        // Loudness
        if ($this->ReadPropertyBoolean('LoudnessControl')) {
            $this->RegisterVariableInteger('Loudness', $this->Translate('Loudness'), 'SONOS.Switch', $positions['Loudness']);
            $this->EnableAction('Loudness');
        } else {
            $this->removeVariableAction('Loudness');
        }

        // NightMode
        if ($this->ReadPropertyBoolean('NightModeControl')) {
            $this->RegisterVariableInteger('NightMode', $this->Translate('Night Mode'), 'SONOS.Switch', $positions['NightMode']);
            $this->EnableAction('NightMode');
            $this->RegisterVariableInteger('DialogLevel', $this->Translate('Dialog Level'), 'SONOS.Switch', $positions['DialogLevel']);
            $this->EnableAction('DialogLevel');
        } else {
            $this->removeVariableAction('NightMode');
            $this->removeVariableAction('DialogLevel');
        }
        // Balance
        if ($this->ReadPropertyBoolean('BalanceControl')) {
            $this->RegisterVariableInteger('Balance', $this->Translate('Balance'), 'SONOS.Balance', $positions['Balance']);
            $this->EnableAction('Balance');
        } else {
            $this->removeVariableAction('Balance');
        }

        // Sleeptimer
        if ($this->ReadPropertyBoolean('SleeptimerControl')) {
            $this->RegisterVariableInteger('Sleeptimer', $this->Translate('Sleeptimer'), '', $positions['Sleeptimer']);
        } else {
            $this->removeVariable('Sleeptimer');
        }

        // PlayMode + Crossfade
        if ($this->ReadPropertyBoolean('PlayModeControl')) {
            $this->RegisterVariableInteger('PlayMode', $this->Translate('Play Mode'), 'SONOS.PlayMode', $positions['PlayMode']);
            $this->RegisterVariableInteger('Crossfade', $this->Translate('Crossfade'), 'SONOS.Switch', $positions['Crossfade']);
            $this->EnableAction('PlayMode');
            $this->EnableAction('Crossfade');
        } else {
            $this->removeVariableAction('PlayMode');
            $this->removeVariableAction('Crossfade');
        }

        // Detailed Now Playing informtion
        if ($this->ReadPropertyBoolean('DetailedInformation')) {
            $this->RegisterVariableString('Details', $this->Translate('Details'), '~HTMLBox', $positions['Details']);
            IPS_SetHidden($this->RegisterVariableString('CoverURL', $this->Translate('Cover URL'), '', $positions['CoverURL']), true);
            IPS_SetHidden($this->RegisterVariableString('ContentStream', $this->Translate('Content Stream'), '', $positions['ContentStream']), true);
            IPS_SetHidden($this->RegisterVariableString('Artist', $this->Translate('Artist'), '', $positions['Artist']), true);
            IPS_SetHidden($this->RegisterVariableString('Title', $this->Translate('Title'), '', $positions['Title']), true);
            IPS_SetHidden($this->RegisterVariableString('Album', $this->Translate('Album'), '', $positions['Album']), true);
            IPS_SetHidden($this->RegisterVariableString('TrackDuration', $this->Translate('Track Duration'), '', $positions['TrackDuration']), true);
            IPS_SetHidden($this->RegisterVariableString('Position', $this->Translate('Position'), '', $positions['Position']), true);
            if (!@$this->GetIDForIdent('StationID')) {
                $vidStationID = $this->RegisterVariableString('StationID', $this->Translate('Station ID'), '', $positions['StationID']);
                IPS_SetHidden($vidStationID, true);
                // in version 1 of sonos StationID was cleared once an hour, but nor sure why. Lets see...
            }
        } else {
            $this->removeVariable('Details');
            $this->removeVariable('CoverURL');
            $this->removeVariable('ContentStream');
            $this->removeVariable('Artist');
            $this->removeVariable('Title');
            $this->removeVariable('Album');
            $this->removeVariable('TrackDuration');
            $this->removeVariable('Position');
            $this->removeVariable('StationID');
        }
        // End Register variables and Actions

        // sorting
        if ($this->ReadPropertyBoolean('ForceOrder')) {
            foreach ($positions as $key => $position) {
                $id = @$this->GetIDForIdent($key);
                if ($id) {
                    IPS_SetPosition($id, $position);
                }
            }
        }

        // Only if IPS is running, check if parameters sould be requested
        // This is required if an instance is newly created, not during startup.
        // During startup this will be handeled by Splitter instance utilizing MessageSink
        if (IPS_GetKernelRunlevel() == KR_READY) {
            if ($this->ReadAttributeInteger('AlbumArtHeight') == -1) {
                $this->SendDataToParent(json_encode([
                    'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                    'type'           => 'AlbumArtRequest',
                    'targetInstance' => $this->InstanceID
                ]));
            }

            if ($this->ReadAttributeInteger('UpdateStatusFrequency') == -1) {
                $this->SendDataToParent(json_encode([
                    'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                    'type'           => 'UpdateStatusFrequencyRequest',
                    'targetInstance' => $this->InstanceID
                ]));
            } else {
                $this->SetTimerInterval('Sonos Update Status', $this->ReadAttributeInteger('UpdateStatusFrequency') * 1000);
            }

            if ($this->ReadAttributeString('RadioStations') == '<undefined>') {
                $this->SendDataToParent(json_encode([
                    'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                    'type'           => 'RadioStationsRequest',
                    'targetInstance' => $this->InstanceID
                ]));
            }
        }
    } // End ApplyChanges

    public function GetConfigurationForm()
    {
        if ($this->ReadPropertyString('RINCON')) {
            $showRINCONButton = false;
        } else {
            $showRINCONButton = true;
        }

        if ($this->ReadPropertyString('IPAddress')) {
            $showRINCONMessage = false;
        } else {
            $showRINCONMessage = true;
        }

        $Form = ['elements' => [
            ['name' => 'IPAddress',             'type' => 'ValidationTextBox', 'caption' => 'IP-Address/Host'],
            [
                'type' => 'RowLayout', 'items' => [
                    ['name' => 'RINCON',        'type' => 'ValidationTextBox', 'caption' => 'RINCON',      'validate' => 'RINCON_[\dA-F]{12}01400'],
                    ['name' => 'rinconButton',  'type' => 'Button',            'caption' => 'read RINCON', 'onClick'  => 'SNS_getRINCON($id,$IPAddress);', 'visible' => $showRINCONButton],
                    ['name' => 'rinconMessage', 'type' => 'Label',             'caption' => 'RINCON can be read automatically, once IP-Address/Host was entered', 'visible'  => $showRINCONMessage]
                ]
            ],
            ['name' => 'TimeOut',               'type' => 'NumberSpinner',     'caption' => 'Maximal ping timeout', 'suffix' => 'ms'],
            ['name' => 'DefaultVolume',         'type' => 'NumberSpinner',     'caption' => 'Default volume',       'suffix' => '%'],
            ['name' => 'RejoinGroup',           'type' => 'CheckBox',          'caption' => 'Rejoin group after unavailability'],
            ['name' => 'MuteControl',           'type' => 'CheckBox',          'caption' => 'Mute Control'],
            ['name' => 'LoudnessControl',       'type' => 'CheckBox',          'caption' => 'Loudness Control'],
            ['name' => 'BassControl',           'type' => 'CheckBox',          'caption' => 'Bass Control'],
            ['name' => 'TrebleControl',         'type' => 'CheckBox',          'caption' => 'Treble Control'],
            ['name' => 'BalanceControl',        'type' => 'CheckBox',          'caption' => 'Balance Control'],
            ['name' => 'SleeptimerControl',     'type' => 'CheckBox',          'caption' => 'Sleeptimer Control'],
            ['name' => 'PlayModeControl',       'type' => 'CheckBox',          'caption' => 'Playmode Control'],
            ['name' => 'NightModeControl',      'type' => 'CheckBox',          'caption' => 'NightMode Control'],
            ['name' => 'DetailedInformation',   'type' => 'CheckBox',          'caption' => 'detailed info'],
            ['name' => 'ForceOrder',            'type' => 'CheckBox',          'caption' => 'Force Variable order']

        ]];
        return json_encode($Form);
    }

    public function ReceiveData($JSONstring)
    {
        $input = json_decode($JSONstring, true);
        switch ($input['type']) {
            case 'grouping':
                if ($input['data']['vanished']) {
                    $this->WriteAttributeBoolean('Vanished', true); // Not available according to SONOS
                    @IPS_SetVariableProfileAssociation('SONOS.Groups', $this->InstanceID, '', '', -1);  // cannot be selected as Group
                    IPS_SetHidden($this->InstanceID, true); // cannot be used, therefore hiding it
                    return;
                }

                $memberOfGroupID = $this->GetIDForIdent('MemberOfGroup');

                if ($this->ReadAttributeBoolean('Vanished')) {
                    $this->WriteAttributeBoolean('Vanished', false);
                    IPS_SetHidden($this->InstanceID, false);
                    if ($this->ReadPropertyBoolean('RejoinGroup')) {
                        $currentGroup = GetValueInteger($memberOfGroupID);
                        if ($currentGroup != 0 && $input['data']['Coordinator'] == 0) {
                            SetValueInteger($memberOfGroupID, 0); // Clear MemberOfGroup, so SetGroup will not consider it as $startGroupCoordinator
                            $this->SetGroup($currentGroup);
                            return;
                        }
                    }
                }

                asort($input['data']['GroupMember']);
                $groupMembers = implode(',', $input['data']['GroupMember']);

                SetValueInteger($memberOfGroupID, $input['data']['Coordinator']);

                if ($input['data']['isCoordinator']) {
                    $hidden = false;
                    @IPS_SetVariableProfileAssociation('SONOS.Groups', $this->InstanceID, IPS_GetName($this->InstanceID), '', -1); // in case it is a cordinator, it can be selected as group
                } else {
                    $hidden = true; // in case Player is not Coordinator, the following variables are hidden, since they are taken from coodrinator
                    @IPS_SetVariableProfileAssociation('SONOS.Groups', $this->InstanceID, '', '', -1); // cannot be selected as Group
                }

                if ($this->ReadAttributeBoolean('Coordinator') != $input['data']['isCoordinator']) {
                    $this->WriteAttributeBoolean('Coordinator', $input['data']['isCoordinator']);

                    @IPS_SetHidden($this->GetIDForIdent('nowPlaying'), $hidden);
                    @IPS_SetHidden($this->GetIDForIdent('Radio'), $hidden);
                    @IPS_SetHidden($this->GetIDForIdent('Playlist'), $hidden);
                    @IPS_SetHidden($this->GetIDForIdent('PlayMode'), $hidden);
                    @IPS_SetHidden($this->GetIDForIdent('Crossfade'), $hidden);
                    @IPS_SetHidden($this->GetIDForIdent('Status'), $hidden);
                    @IPS_SetHidden($this->GetIDForIdent('Sleeptimer'), $hidden);
                    @IPS_SetHidden($this->GetIDForIdent('Details'), $hidden);
                }
                if ($this->ReadAttributeString('GroupMembers') != $groupMembers) {
                    $this->WriteAttributeString('GroupMembers', $groupMembers);
                    if (count($input['data']['GroupMember'])) {  // at least one member exists
                        @IPS_SetHidden($this->GetIDForIdent('GroupVolume'), false);
                        @IPS_SetHidden($this->GetIDForIdent('MemberOfGroup'), true);
                    } else {
                        @IPS_SetHidden($this->GetIDForIdent('GroupVolume'), true);
                        @IPS_SetHidden($this->GetIDForIdent('MemberOfGroup'), false);
                    }
                }
                break;
            case 'updateStatus':
                $this->WriteAttributeInteger('UpdateStatusFrequency', $input['data']);
                $this->SetTimerInterval('Sonos Update Status', $input['data'] * 1000);
                break;
            case 'RadioStations':
                $this->WriteAttributeString('RadioStations', $input['data']);
                break;
            case 'AlbumArtHight':
                $this->WriteAttributeInteger('AlbumArtHeight', $input['data']);
                break;
            case 'checkPlaylistAction':
                $this->checkPlaylistAction();
                break;
            case 'callFunction':
                switch ($input['function']) {
                    case 'ChangeVolume':
                        $this->ChangeVolume($input['volume']);
                        break;
                    case 'DeleteSleepTimer':
                        $this->DeleteSleepTimer();
                        break;
                    case 'Next':
                        $this->Next();
                        break;
                    case 'Pause':
                        $this->Pause();
                        break;
                    case 'Play':
                        $this->Play();
                        break;
                    case 'Previous':
                        $this->Previous();
                        break;
                    case 'SetCrossfade':
                        $this->SetCrossfade($input['crossfade']);
                        break;
                    case 'SetDefaultVolume':
                        $this->SetDefaultVolume();
                        break;
                    case 'SetGroup':
                        $this->SetGroup($input['coordinator']);
                        break;
                    case 'SetPlayMode':
                        $this->SetPlayMode($input['playmode']);
                        break;
                    case 'SetSleepTimer':
                        $this->SetSleepTimer($input['sleeptimer']);
                        break;
                    case 'SetVolume':
                        $this->SetVolume($input['volume']);
                        break;
                    case 'Stop':
                        $this->Stop();
                        break;
                }
                break;
            case 'removeMember':
                $currentMembers = explode(',', $this->ReadAttributeString('GroupMembers'));
                $currentMembers = array_filter($currentMembers, function ($v) {
                    return $v != '';
                });
                $currentMembers = array_filter($currentMembers, function ($v) use ($input) {
                    return $v != $input['instanceID'];
                });
                asort($currentMembers);
                $this->WriteAttributeString('GroupMembers', implode(',', $currentMembers));

                if (!count($currentMembers)) {
                    IPS_SetHidden($this->GetIDForIdent('GroupVolume'), true);
                    IPS_SetHidden($this->GetIDForIdent('MemberOfGroup'), false);
                }
                break;
            case 'addMember':
                $currentMembers = explode(',', $this->ReadAttributeString('GroupMembers'));
                $currentMembers = array_filter($currentMembers, function ($v) {
                    return $v != '';
                });
                $currentMembers = array_filter($currentMembers, function ($v) use ($input) {
                    return $v != $input['instanceID'];    // also remove instance to add to make sure no duplicates exist
                });
                $currentMembers[] = $input['instanceID'];

                asort($currentMembers);
                $this->WriteAttributeString('GroupMembers', implode(',', $currentMembers));

                IPS_SetHidden($this->GetIDForIdent('GroupVolume'), false);
                IPS_SetHidden($this->GetIDForIdent('MemberOfGroup'), true);
                break;
            case 'setVariable':
                $vid = @$this->GetIDForIdent($input['variableIdent']);
                if ($vid) {
                    switch ($input['variableType']) {
                        case 'int':
                            SetValueInteger($vid, $input['variableValue']);
                            break;
                    }
                }
                break;
            case 'setAttribute':
                switch ($input['attributeType']) {
                    case 'string':
                        $this->WriteAttributeString($input['attributeName'], $input['attributeValue']);
                        break;
                    case 'bool':
                        $this->WriteAttributeBoolean($input['attributeName'], $input['attributeValue']);
                        break;
                }
                break;
            default:
                throw new Exception($this->Translate('unknown type in ReceiveData'));
        }
    }

    // public Functions for End users
    public function alexaResponse()
    {
        $response = [];

        if ($this->ReadAttributeBoolean('Coordinator')) {
            $response['Coordinator'] = 'true';
        } else {
            $response['Coordinator'] = 'false';
        }

        // Group Members
        $name_array = [];
        foreach (explode(',', $this->ReadAttributeString('GroupMembers')) as $key => $instanceID) {
            if ($instanceID == 0) {
                $name_array[] = 'none';
            } else {
                $name_array[] = IPS_GetName($instanceID);
            }
        }
        $response['GroupMembers'] = implode(',', $name_array);

        $this->alexa_get_value('MemberOfGroup', 'fromatted', $response);
        $this->alexa_get_value('GroupVolume', 'fromatted', $response);
        $this->alexa_get_value('ContentStream', 'string', $response);
        $this->alexa_get_value('Artist', 'string', $response);
        $this->alexa_get_value('Title', 'string', $response);
        $this->alexa_get_value('Album', 'string', $response);
        $this->alexa_get_value('TrackDuration', 'string', $response);
        $this->alexa_get_value('Position', 'string', $response);
        $this->alexa_get_value('nowPlaying', 'string', $response);
        $this->alexa_get_value('Radio', 'fromatted', $response);
        $this->alexa_get_value('Status', 'fromatted', $response);
        $this->alexa_get_value('Volume', 'fromatted', $response);
        $this->alexa_get_value('Mute', 'fromatted', $response);
        $this->alexa_get_value('NightMode', 'fromatted', $response);
        $this->alexa_get_value('DialogLevel', 'fromatted', $response);
        $this->alexa_get_value('Loudness', 'fromatted', $response);
        $this->alexa_get_value('Bass', 'fromatted', $response);
        $this->alexa_get_value('Treble', 'fromatted', $response);
        $this->alexa_get_value('Balance', 'fromatted', $response);
        $this->alexa_get_value('Sleeptimer', 'string', $response);
        $this->alexa_get_value('PlayMode', 'fromatted', $response);
        $this->alexa_get_value('Crossfade', 'fromatted', $response);

        return $response;
    }

    public function ChangeGroupVolume(int $increment)
    {
        if (!@$this->ReadAttributeBoolean('Coordinator')) {
            die($this->Translate('This function is only allowed for Coordinators'));
        }

        $groupMembers = $this->ReadAttributeString('GroupMembers');
        $groupMembersArray = [];
        if ($groupMembers) {
            $groupMembersArray = array_map('intval', explode(',', $groupMembers));
        }
        $groupMembersArray[] = $this->InstanceID;

        foreach ($groupMembersArray as $key => $ID) {
            $newVolume = (GetValueInteger(IPS_GetObjectIDByIdent('Volume', $ID)) + $increment);
            if ($newVolume > 100) {
                $newVolume = 100;
            } elseif ($newVolume < 0) {
                $newVolume = 0;
            }
            try {
                $this->SendDataToParent(json_encode([
                    'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                    'type'           => 'callFunction',
                    'targetInstance' => $ID,
                    'function'       => 'SetVolume',
                    'volume'         => $newVolume
                ]));
            } catch (Exception $e) {
            }
        }

        $GroupVolume = 0;
        foreach ($groupMembersArray as $key => $ID) {
            $GroupVolume += GetValueInteger(IPS_GetObjectIDByIdent('Volume', $ID));
        }

        SetValueInteger($this->GetIDForIdent('GroupVolume'), intval(round($GroupVolume / count($groupMembersArray))));
    }

    public function ChangeVolume(int $increment)
    {
        $newVolume = (GetValueInteger($this->GetIDForIdent('Volume')) + $increment);

        if ($newVolume > 100) {
            $newVolume = 100;
        } elseif ($newVolume < 0) {
            $newVolume = 0;
        }
        try {
            $this->SetVolume($newVolume);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function DelegateGroupCoordinationTo(int $newGroupCoordinator, bool $rejoinGroup)
    {
        // do nothing if instance is the same as $newGroupCoordinator
        if ($this->InstanceID === $newGroupCoordinator) {
            return;
        }

        // $newGroupCoordinator is not part of group
        if (GetValueInteger(IPS_GetObjectIDByIdent('MemberOfGroup', $newGroupCoordinator)) !== $this->InstanceID) {
            throw new Exception(sprintf($this->translate('%s is not a member of this group'), $newGroupCoordinator));
        }

        // execute sonos change
        $ip = $this->getIP();

        (new SonosAccess($ip))->DelegateGroupCoordinationTo(IPS_GetProperty($newGroupCoordinator, 'RINCON'), $rejoinGroup);

        // get old membersOf and remove involved instances
        $currentMembers = explode(',', $this->ReadAttributeString('GroupMembers'));
        $currentMembers = array_filter($currentMembers, function ($v) {
            return $v != '';
        });
        $currentMembers = array_filter($currentMembers, function ($v) {
            return $v != $this->InstanceID;
        });
        //$currentMembers = array_filter($currentMembers, function($v) { return $v != $newGroupCoordinator ; });

        // update memberOf in all members, and clear in new coordinator
        foreach ($currentMembers as $key => $ID) {
            if ($ID != $newGroupCoordinator) {
                $this->SendDataToParent(json_encode([
                    'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                    'type'           => 'setVariable',
                    'targetInstance' => $ID,
                    'variableType'   => 'int',
                    'variableIdent'  => 'MemberOfGroup',
                    'variableValue'  => $newGroupCoordinator
                ]));
                $newMembers[] = $ID;
            } else {
                $this->SendDataToParent(json_encode([
                    'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                    'type'           => 'setVariable',
                    'targetInstance' => $ID,
                    'variableType'   => 'int',
                    'variableIdent'  => 'MemberOfGroup',
                    'variableValue'  => 0
                ]));
            }
        }

        // update GroupMembers in old and new coordinator
        if ($rejoinGroup) {
            $newMembers[] = $this->InstanceID;
        }

        asort($newMembers);
        $this->SendDataToParent(json_encode([
            'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
            'type'           => 'setAttribute',
            'targetInstance' => $newGroupCoordinator,
            'attributeType'  => 'string',
            'attributeName'  => 'GroupMembers',
            'attributeValue' => implode(',', $newMembers)
        ]));
        $this->WriteAttributeString('GroupMembers', '');

        // clear memberOf in new coordinator, set memberOf in old coordinator
        if ($rejoinGroup) {
            SetValueInteger($this->GetIDForIdent('MemberOfGroup'), $newGroupCoordinator);
        } else {
            SetValueInteger($this->GetIDForIdent('MemberOfGroup'), 0);
        }

        // switch variable "Coordinator", achtung: $rejoinGroup
        if ($rejoinGroup) {
            $this->WriteAttributeBoolean('Coordinator', false);
        } else {
            $this->WriteAttributeBoolean('Coordinator', true);
        }

        $this->SendDataToParent(json_encode([
            'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
            'type'           => 'setAttribute',
            'targetInstance' => $newGroupCoordinator,
            'attributeType'  => 'bool',
            'attributeName'  => 'Coordinator',
            'attributeValue' => true
        ]));

        // update SONOS.Groups, achtung: $rejoinGroup
        if ($rejoinGroup) {
            @IPS_SetVariableProfileAssociation('SONOS.Groups', $this->InstanceID, '', '', -1);
        } else {
            @IPS_SetVariableProfileAssociation('SONOS.Groups', $this->InstanceID, IPS_GetName($this->InstanceID), '', -1);
        }
        @IPS_SetVariableProfileAssociation('SONOS.Groups', $newGroupCoordinator, IPS_GetName($newGroupCoordinator), '', -1);

        // Variablen anzeigen und verstecken.
        // new coordinator
        // TODO --> Verstecken oder auch nicht.
        @IPS_SetHidden(IPS_GetObjectIDByIdent('GroupVolume', $newGroupCoordinator), false);
        @IPS_SetHidden(IPS_GetObjectIDByIdent('MemberOfGroup', $newGroupCoordinator), true);
        @IPS_SetHidden(IPS_GetObjectIDByIdent('nowPlaying', $newGroupCoordinator), false);
        @IPS_SetHidden(IPS_GetObjectIDByIdent('Radio', $newGroupCoordinator), false);
        @IPS_SetHidden(IPS_GetObjectIDByIdent('Playlist', $newGroupCoordinator), false);
        @IPS_SetHidden(IPS_GetObjectIDByIdent('PlayMode', $newGroupCoordinator), false);
        @IPS_SetHidden(IPS_GetObjectIDByIdent('Crossfade', $newGroupCoordinator), false);
        @IPS_SetHidden(IPS_GetObjectIDByIdent('Status', $newGroupCoordinator), false);
        @IPS_SetHidden(IPS_GetObjectIDByIdent('Sleeptimer', $newGroupCoordinator), false);
        @IPS_SetHidden(IPS_GetObjectIDByIdent('Details', $newGroupCoordinator), false);

        // old coordinator
        if ($rejoinGroup) {
            $hidden = true;
        } else {
            $hidden = false;
        }

        @IPS_SetHidden($this->GetIDForIdent('GroupVolume'), true);
        @IPS_SetHidden($this->GetIDForIdent('MemberOfGroup'), false);
        @IPS_SetHidden($this->GetIDForIdent('nowPlaying'), $hidden);
        @IPS_SetHidden($this->GetIDForIdent('Radio'), $hidden);
        @IPS_SetHidden($this->GetIDForIdent('Playlist'), $hidden);
        @IPS_SetHidden($this->GetIDForIdent('PlayMode'), $hidden);
        @IPS_SetHidden($this->GetIDForIdent('Crossfade'), $hidden);
        @IPS_SetHidden($this->GetIDForIdent('Status'), $hidden);
        @IPS_SetHidden($this->GetIDForIdent('Sleeptimer'), $hidden);
        @IPS_SetHidden($this->GetIDForIdent('Details'), $hidden);
    } // END DelegateGroupCoordinationTo

    public function DeleteSleepTimer()
    {
        $targetInstance = $this->findTarget();

        if ($targetInstance === $this->InstanceID) {
            $ip = $this->getIP();

            (new SonosAccess($ip))->SetSleeptimer(0, 0, 0);
        } else {
            $this->SendDataToParent(json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'callFunction',
                'targetInstance' => $targetInstance,
                'function'       => 'DeleteSleepTimer'
            ]));
        }
    }  // END DeleteSleepTimer

    public function Next()
    {
        $targetInstance = $this->findTarget();

        if ($targetInstance === $this->InstanceID) {
            $ip = $this->getIP();

            (new SonosAccess($ip))->Next();
        } else {
            $this->SendDataToParent(json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'callFunction',
                'targetInstance' => $targetInstance,
                'function'       => 'Next'
            ]));
        }
    } // END Next

    public function Pause()
    {
        $targetInstance = $this->findTarget();

        if ($targetInstance === $this->InstanceID) {
            $ip = $this->getIP();

            SetValue($this->GetIDForIdent('Status'), 2);
            $sonos = new SonosAccess($ip);
            if ($sonos->GetTransportInfo() == 1) {
                $sonos->Pause();
            }
        } else {
            $this->SendDataToParent(json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'callFunction',
                'targetInstance' => $targetInstance,
                'function'       => 'Pause'
            ]));
        }
    }   // END Pause

    public function Play()
    {
        $targetInstance = $this->findTarget();

        if ($targetInstance === $this->InstanceID) {
            $ip = $this->getIP();

            SetValue($this->GetIDForIdent('Status'), 1);
            (new SonosAccess($ip))->Play();
        } else {
            $this->SendDataToParent(json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'callFunction',
                'targetInstance' => $targetInstance,
                'function'       => 'Play'
            ]));
        }
    }

    public function PlayFiles(string $files, string $volumeChange)
    {
        $ip = $this->getIP();
        $filesArray = json_decode($files, true);

        $sonos = new SonosAccess($ip);

        $positionInfo = $sonos->GetPositionInfo();
        $mediaInfo = $sonos->GetMediaInfo();
        $transportInfo = $sonos->GetTransportInfo();
        $isGroupCoordinator = @$this->ReadAttributeBoolean('Coordinator');
        $volumeList = [];
        $volumeList[$this->InstanceID] = GetValueInteger($this->GetIDForIdent('Volume'));
        if ($isGroupCoordinator) {
            foreach (explode(',', $this->ReadAttributeString('GroupMembers')) as $groupMember) {
                if ($groupMember == 0) {
                    continue;
                }
                $volumeList[$groupMember] = GetValueInteger(IPS_GetObjectIDByIdent('Volume', $groupMember));
            }
        }

        //adjust volume if needed
        if ($volumeChange != 0) {
            // pause if playing or remove from group
            if (!$isGroupCoordinator) {
                $this->SetGroup(0);
            } elseif ($transportInfo == 1) {
                try {
                    $sonos->Pause();
                } catch (Exception $e) {
                    if ($e->getMessage() != 'Error during Soap Call: UPnPError s:Client 701 (ERROR_AV_UPNP_AVT_INVALID_TRANSITION)') {
                        throw $e;
                    }
                }
            }

            // volume request absolute or relative?
            if ($volumeChange[0] == '+' || $volumeChange[0] == '-') {
                $function = 'ChangeVolume';
            } else {
                $function = 'SetVolume';
            }

            foreach ($volumeList as $ID => $volume) {
                $this->SendDataToParent(json_encode([
                    'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                    'type'           => 'callFunction',
                    'targetInstance' => $ID,
                    'function'       => $function,
                    'volume'         => $volumeChange
                ]));
            }
        }

        foreach ($filesArray as $key => $file) {
            // only files on SMB share or http server can be used
            if (preg_match('/^\/\/[\w,.,\d,-]*\/\S*/', $file) == 1) {
                $uri = 'x-file-cifs:' . $file;
            } elseif (preg_match('/^https{0,1}:\/\/[\w,.,\d,\-,:]*\/\S*/', $file) == 1) {
                $uri = $file;
            } elseif ($file == '') {
                throw new Exception($this->Translate('No file handed over.'));
            } else {
                throw new Exception(sprintf($this->Translate('File (%s) has to be located on a Samba share (e.g. //ipsymcon.fritz.box/tts/text.mp3) or a HTTP server (e.g. http://ipsymcon.fritz.box/tts/text.mp3)'), $file));
            }

            $sonos->SetAVTransportURI($uri);
            $sonos->SetPlayMode(0);
            $sonos->Play();
            IPS_Sleep(500);
            $fileTransportInfo = $sonos->GetTransportInfo();
            while ($fileTransportInfo == 1 || $fileTransportInfo == 5) {
                IPS_Sleep(200);
                $fileTransportInfo = $sonos->GetTransportInfo();
            }
        }

        // reset to what was playing before
        $sonos->SetAVTransportURI($mediaInfo['CurrentURI'], $mediaInfo['CurrentURIMetaData']);
        if ($positionInfo['TrackDuration'] != '0:00:00' && $positionInfo['Track'] > 1) {
            try {
                $sonos->Seek('TRACK_NR', $positionInfo['Track']);
            } catch (Exception $e) {
            }
        }
        if ($positionInfo['TrackDuration'] != '0:00:00' && $positionInfo['RelTime'] != 'NOT_IMPLEMENTED') {
            try {
                $sonos->Seek('REL_TIME', $positionInfo['RelTime']);
            } catch (Exception $e) {
            }
        }

        if ($volumeChange != 0) {
            // set back volume
            foreach ($volumeList as $ID => $volume) {
                $this->SendDataToParent(json_encode([
                    'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                    'type'           => 'callFunction',
                    'targetInstance' => $ID,
                    'function'       => 'SetVolume',
                    'volume'         => $volume
                ]));
            }
        }

        // If it was playing before, play again
        if ($transportInfo == 1) {
            $sonos->Play();
        }
    } // END PlayFiles

    public function PlayFilesGrouping(string $instances, string $files, string $volumeChange)
    {
        $ip = $this->getIP();
        $instancesArray = json_decode($instances, true);

        $sonos = new SonosAccess($ip);
        $transportInfo = $sonos->GetTransportInfo();
        $volume = GetValueInteger($this->GetIDForIdent('Volume'));

        // pause if playing
        if ($transportInfo == 1) {
            try {
                $sonos->Pause();
            } catch (Exception $e) {
                if ($e->getMessage() != 'Error during Soap Call: UPnPError s:Client 701 (ERROR_AV_UPNP_AVT_INVALID_TRANSITION)') {
                    throw $e;
                }
            }
        }

        if ($volumeChange != 0) {
            // volume request absolte or relative?
            if ($volumeChange[0] == '+' || $volumeChange[0] == '-') {
                $this->ChangeVolume($volumeChange);
            } else {
                $this->SetVolume($volumeChange);
            }
        }

        foreach ($instancesArray as $instanceID => &$settings) {
            $ip = gethostbyname(IPS_GetProperty($instanceID, 'IPAddress'));
            $timeout = $this->ReadPropertyInteger('TimeOut');
            if ($timeout && Sys_Ping($ip, $timeout) != true) {
                if (Sys_Ping($ip, $timeout) != true) {
                    $settings['available'] = false;
                    continue;
                }
            }

            $settings['available'] = true;
            $settings['sonos'] = new SonosAccess($ip);
            $settings['mediaInfo'] = $settings['sonos']->GetMediaInfo();
            $settings['positionInfo'] = $settings['sonos']->GetPositionInfo();
            $settings['transportInfo'] = $settings['sonos']->GetTransportInfo();
            $settings['group'] = GetValueInteger(IPS_GetObjectIDByIdent('MemberOfGroup', $instanceID));
            $settings['volumeBefore'] = GetValueInteger(IPS_GetObjectIDByIdent('Volume', $instanceID));

            if (isset($settings['volume']) && $settings['volume'] != 0) {
                // volume request absolte or relative?
                if ($settings['volume'][0] == '+' || $settings['volume'][0] == '-') {
                    $this->SendDataToParent(json_encode([
                        'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                        'type'           => 'callFunction',
                        'targetInstance' => $instanceID,
                        'function'       => 'ChangeVolume',
                        'volume'         => $settings['volume']
                    ]));
                } else {
                    $this->SendDataToParent(json_encode([
                        'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                        'type'           => 'callFunction',
                        'targetInstance' => $instanceID,
                        'function'       => 'SetVolume',
                        'volume'         => $settings['volume']
                    ]));
                }
            }

            $this->SendDataToParent(json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'callFunction',
                'targetInstance' => $instanceID,
                'function'       => 'SetGroup',
                'coordinator'    => $this->InstanceID
            ]));
        }
        unset($settings);

        $this->PlayFiles($files, 0); // 0 -> no volume change!

        foreach ($instancesArray as $instanceID => $settings) {
            if ($settings['available'] == false) {
                continue;
            }
            $this->SendDataToParent(json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'callFunction',
                'targetInstance' => $instanceID,
                'function'       => 'SetGroup',
                'coordinator'    => $settings['group']
            ]));
            $settings['sonos']->SetAVTransportURI($settings['mediaInfo']['CurrentURI'], $settings['mediaInfo']['CurrentURIMetaData']);
            if (@$settings['mediaInfo']['Track'] > 1) {
                try {
                    $settings['sonos']->Seek('TRACK_NR', $settings['mediaInfo']['Track']);
                } catch (Exception $e) {
                }
            }
            if ($settings['positionInfo']['TrackDuration'] != '0:00:00' && $settings['positionInfo']['RelTime'] != 'NOT_IMPLEMENTED') {
                try {
                    $settings['sonos']->Seek('REL_TIME', $settings['positionInfo']['RelTime']);
                } catch (Exception $e) {
                }
            }
            $this->SendDataToParent(json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'callFunction',
                'targetInstance' => $instanceID,
                'function'       => 'SetVolume',
                'volume'         => $settings['volumeBefore']
            ]));
            if ($settings['transportInfo'] == 1 && !$settings['group']) {
                $this->SendDataToParent(json_encode([
                    'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                    'type'           => 'callFunction',
                    'targetInstance' => $instanceID,
                    'function'       => 'Play'
                ]));
            }
        }

        if ($volumeChange != 0) {
            // set back volume
            $this->SetVolume($volume);
        }

        if ($transportInfo == 1) {
            $sonos->Play();
        }
    } // END PlayFilesGrouping

    public function Previous()
    {
        $targetInstance = $this->findTarget();

        if ($targetInstance === $this->InstanceID) {
            $ip = $this->getIP();

            (new SonosAccess($ip))->Previous();
        } else {
            $this->SendDataToParent(json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'callFunction',
                'targetInstance' => $targetInstance,
                'function'       => 'Previous'
            ]));
        }
    } // END Previous

    public function RampToVolume(string $rampType, int $volume)
    {
        $ip = $this->getIP();

        SetValue($this->GetIDForIdent('Volume'), $volume);
        (new SonosAccess($ip))->RampToVolume($rampType, $volume);
    } // END RampToVolume

    public function SetAnalogInput(int $input_instance)
    {
        $ip = $this->getIP();

        if (@GetValue($this->GetIDForIdent('MemberOfGroup'))) {
            $this->SetGroup(0);
        }

        (new SonosAccess($ip))->SetAVTransportURI('x-rincon-stream:' . IPS_GetProperty($input_instance, 'RINCON'));
    }    // END SetAnalogInput

    public function SetBalance(int $balance)
    {
        $ip = $this->getIP();

        $leftVolume = 100;
        $rightVolume = 100;
        if ($balance < 0) {
            $rightVolume = 100 + $balance;
        } else {
            $leftVolume = 100 - $balance;
        }

        $sonos = (new SonosAccess($ip));
        $sonos->SetVolume($leftVolume, 'LF');
        $sonos->SetVolume($rightVolume, 'RF');
        if (!$this->ReadPropertyBoolean('BalanceControl')) {
            SetValue($this->GetIDForIdent('Balance'), $balance);
        }
    } // END SetBalance

    public function SetBass(int $bass)
    {
        $ip = $this->getIP();
        (new SonosAccess($ip))->SetBass($bass);
        if (!$this->ReadPropertyBoolean('BassControl')) {
            SetValue($this->GetIDForIdent('Bass'), $bass);
        }
    }    // END SetBass

    public function SetCrossfade(bool $crossfade)
    {
        $targetInstance = $this->findTarget();

        if ($targetInstance === $this->InstanceID) {
            $ip = $this->getIP();

            (new SonosAccess($ip))->SetCrossfade($crossfade);
            if ($this->ReadPropertyBoolean('PlayModeControl')) {
                SetValue($this->GetIDForIdent('Crossfade'), $crossfade);
            }
        } else {
            $this->SendDataToParent(json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'callFunction',
                'targetInstance' => $targetInstance,
                'function'       => 'SetCrossfade',
                'crossfade'      => $crossfade
            ]));
        }
    }   // END SetCrossfade

    public function SetDefaultGroupVolume()
    {
        if (!@$this->ReadAttributeBoolean('Coordinator')) {
            die('This function is only allowed for Coordinators');
        }

        $groupMembers = $this->ReadAttributeString('GroupMembers');
        $groupMembersArray = [];
        if ($groupMembers) {
            $groupMembersArray = array_map('intval', explode(',', $groupMembers));
        }
        $groupMembersArray[] = $this->InstanceID;

        foreach ($groupMembersArray as $key => $ID) {
            try {
                $this->SendDataToParent(json_encode([
                    'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                    'type'           => 'callFunction',
                    'targetInstance' => $ID,
                    'function'       => 'SetDefaultVolume'
                ]));
            } catch (Exception $e) {
            }
        }

        $GroupVolume = 0;
        foreach ($groupMembersArray as $key => $ID) {
            $GroupVolume += GetValueInteger(IPS_GetObjectIDByIdent('Volume', $ID));
        }

        SetValueInteger($this->GetIDForIdent('GroupVolume'), intval(round($GroupVolume / count($groupMembersArray))));
    }    // END  SetDefaultGroupVolume

    public function SetDefaultVolume()
    {
        try {
            $this->SetVolume($this->ReadPropertyInteger('DefaultVolume'));
        } catch (Exception $e) {
            throw $e;
        }
    } // SetDefaultVolume

    public function SetDialogLevel(bool $dialogLevel)
    {
        $ip = $this->getIP();

        (new SonosAccess($ip))->SetDialogLevel($dialogLevel);
        if ($this->ReadPropertyBoolean('NightModeControl')) {  // same switch as Night Mode
            SetValue($this->GetIDForIdent('DialogLevel'), $dialogLevel);
        }
    }   // END SetNightMode

    public function SetGroup(int $groupCoordinator)
    {
        // Instance has Members, do nothing
        if (@$this->ReadAttributeString('GroupMembers')) {
            return;
        }
        // Do not try to assign to itself
        if ($this->InstanceID === $groupCoordinator) {
            $groupCoordinator = 0;
        }

        $startGroupCoordinator = GetValue($this->GetIDForIdent('MemberOfGroup'));

        $ip = $this->getIP();

        // cleanup old group
        if ($startGroupCoordinator) {
            $this->SendDataToParent(json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'removeMember',
                'targetInstance' => $startGroupCoordinator,
                'instanceID'     => $this->InstanceID
            ]));
        }

        // adjust new Group
        $currentMembers = [];
        if ($groupCoordinator) {
            $this->SendDataToParent(json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'addMember',
                'targetInstance' => $groupCoordinator,
                'instanceID'     => $this->InstanceID
            ]));

            $uri = 'x-rincon:' . IPS_GetProperty($groupCoordinator, 'RINCON');
            $this->WriteAttributeBoolean('Coordinator', false);
            @IPS_SetVariableProfileAssociation('SONOS.Groups', $this->InstanceID, '', '', -1);
        } else {
            $uri = '';
            $this->WriteAttributeBoolean('Coordinator', true);
            @IPS_SetVariableProfileAssociation('SONOS.Groups', $this->InstanceID, IPS_GetName($this->InstanceID), '', -1);
        }

        // update coordinator members
        SetValue($this->GetIDForIdent('MemberOfGroup'), $groupCoordinator);

        // Set relevant variables to hidden/unhidden
        if ($groupCoordinator) {
            $hidden = true;
        } else {
            $hidden = false;
        }
        @IPS_SetHidden($this->GetIDForIdent('nowPlaying'), $hidden);
        @IPS_SetHidden($this->GetIDForIdent('Radio'), $hidden);
        @IPS_SetHidden($this->GetIDForIdent('Playlist'), $hidden);
        @IPS_SetHidden($this->GetIDForIdent('PlayMode'), $hidden);
        @IPS_SetHidden($this->GetIDForIdent('Crossfade'), $hidden);
        @IPS_SetHidden($this->GetIDForIdent('Status'), $hidden);
        @IPS_SetHidden($this->GetIDForIdent('Sleeptimer'), $hidden);
        @IPS_SetHidden($this->GetIDForIdent('Details'), $hidden);
        // always hide GroupVolume, unhide executed on GroupCoordinator a few lines above
        @IPS_SetHidden($this->GetIDForIdent('GroupVolume'), true);
        @IPS_SetHidden($this->GetIDForIdent('MemberOfGroup'), false);

        (new SonosAccess($ip))->SetAVTransportURI($uri);
    } // END SetGroup

    public function SetGroupVolume(int $volume)
    {
        if (!@$this->ReadAttributeBoolean('Coordinator')) {
            die($this->Translate('This function is only allowed for Coordinators'));
        }

        $this->ChangeGroupVolume($volume - GetValue($this->GetIDForIdent('GroupVolume')));
    }    // END SetGroupVolume

    public function SetHdmiInput(int $input_instance)
    {
        // seems to be the same as S/PDIF
        $this->SetSpdifInput($input_instance);
    }    // END SetHdmiInput

    public function SetLoudness(bool $loudness)
    {
        $ip = $this->getIP();

        (new SonosAccess($ip))->SetLoudness($loudness);
        if ($this->ReadPropertyBoolean('LoudnessControl')) {
            SetValue($this->GetIDForIdent('Loudness'), $loudness);
        }
    } //  END SetLoudness

    public function SetMute(bool $mute)
    {
        $ip = $this->getIP();

        (new SonosAccess($ip))->SetMute($mute);
        if ($this->ReadPropertyBoolean('MuteControl')) {
            SetValue($this->GetIDForIdent('Mute'), $mute);
        }
    }   // END SetMute

    public function SetNightMode(bool $nightMode)
    {
        $ip = $this->getIP();

        (new SonosAccess($ip))->SetNightMode($nightMode);
        if ($this->ReadPropertyBoolean('NightModeControl')) {
            SetValue($this->GetIDForIdent('NightMode'), $nightMode);
        }
    }   // END SetNightMode

    public function SetPlaylist(string $name)
    {
        $ip = $this->getIP();

        if (@GetValue($this->GetIDForIdent('MemberOfGroup'))) {
            $this->SetGroup(0);
        }

        $sonos = new SonosAccess($ip);

        $uri = '';
        $meta = '';

        foreach ((new SimpleXMLElement($sonos->BrowseContentDirectory('SQ:', 'BrowseDirectChildren', 999)['Result']))->container as $container) {
            if ($container->xpath('dc:title')[0] == $name) {
                $uri = (string) $container->res;
                break;
            }
        }

        if ($uri === '') {
            foreach ((new SimpleXMLElement($sonos->BrowseContentDirectory('FV:2', 'BrowseDirectChildren', 999)['Result']))->item as $item) {
                if (preg_replace($this->getPlaylistReplacementFrom(), $this->getPlaylistReplacementTo(), $item->xpath('dc:title')[0]) == $name) {
                    $uri = (string) $item->res;
                    $meta = (string) $item->xpath('r:resMD')[0];
                    break;
                }
            }
        }

        if ($uri === '') {
            foreach ((new SimpleXMLElement($sonos->BrowseContentDirectory('A:PLAYLISTS', 'BrowseDirectChildren', 999)['Result']))->container as $container) {
                if (preg_replace($this->getPlaylistReplacementFrom(), $this->getPlaylistReplacementTo(), $container->xpath('dc:title')[0]) == $name) {
                    $uri = (string) $container->res;
                    break;
                }
            }
        }

        if ($uri === '') {
            throw new Exception('Playlist \'' . $name . '\' not found');
        }

        $sonos->ClearQueue();
        $sonos->AddToQueue($uri, $meta);
        $sonos->SetAVTransportURI('x-rincon-queue:' . $this->ReadPropertyString('RINCON') . '#0');
    }    // END SetPlaylist

    public function SetPlayMode(int $playMode)
    {
        $targetInstance = $this->findTarget();

        if ($targetInstance === $this->InstanceID) {
            $ip = $this->getIP();

            (new SonosAccess($ip))->SetPlayMode($playMode);
            if ($this->ReadPropertyBoolean('PlayModeControl')) {
                SetValue($this->GetIDForIdent('PlayMode'), $playMode);
            }
        } else {
            $this->SendDataToParent(json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'callFunction',
                'targetInstance' => $targetInstance,
                'function'       => 'SetPlayMode',
                'playmode'       => $playMode
            ]));
        }
    } // END etPlayMode

    public function SetRadio(string $radio)
    {
        $ip = $this->getIP();

        if (@GetValue($this->GetIDForIdent('MemberOfGroup'))) {
            $this->SetGroup(0);
        }

        $sonos = new SonosAccess($ip);

        $uri = '';

        // try to find Radio Station URL
        try {
            $uri = $this->getRadioURL($radio);
        } catch (Exception $e) {
            // not found in Splitter instance
            // check in TuneIn Favorites
            foreach ((new SimpleXMLElement($sonos->BrowseContentDirectory('R:0/0')['Result']))->item as $item) {
                if ($item->xpath('dc:title')[0] == $radio) {
                    $uri = (string) $item->res;
                    break;
                }
            }
        }

        if ($uri == '') {
            throw new Exception(sprintf($this->Translate('Radio station "%s" not found'), $radio));
        }

        $sonos->SetRadio($uri, $radio);
    } // END SetRadio

    public function SetSleepTimer(int $minutes)
    {
        $targetInstance = $this->findTarget();

        if ($targetInstance === $this->InstanceID) {
            $ip = $this->getIP();

            $hours = 0;

            while ($minutes > 59) {
                $hours = $hours + 1;
                $minutes = $minutes - 60;
            }

            (new SonosAccess($ip))->SetSleeptimer($hours, $minutes, 0);
        } else {
            $this->SendDataToParent(json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'callFunction',
                'targetInstance' => $targetInstance,
                'function'       => 'SetSleepTimer',
                'sleeptimer'     => $minutes
            ]));
        }
    } // SetSleepTimer

    public function SetSpdifInput(int $input_instance)
    {
        $ip = $this->getIP();

        if (@GetValue($this->GetIDForIdent('MemberOfGroup'))) {
            $this->SetGroup(0);
        }

        (new SonosAccess($ip))->SetAVTransportURI('x-sonos-htastream:' . IPS_GetProperty($input_instance, 'RINCON') . ':spdif');
    } // END SetSpdifInput

    public function SetTransportURI(string $uri)
    {
        $ip = $this->getIP();

        if (@GetValue($this->GetIDForIdent('MemberOfGroup'))) {
            $this->SetGroup(0);
        }

        (new SonosAccess($ip))->SetAVTransportURI($uri);
    } // END SetTransportURI

    public function SetTreble(int $treble)
    {
        $ip = $this->getIP();

        (new SonosAccess($ip))->SetTreble($treble);
        if (!$this->ReadPropertyBoolean('TrebleControl')) {
            SetValue($this->GetIDForIdent('Treble'), $treble);
        }
    } // END SetTreble

    public function SetVolume(int $volume)
    {
        $ip = $this->getIP();

        SetValue($this->GetIDForIdent('Volume'), $volume);
        (new SonosAccess($ip))->SetVolume($volume);
    } // END SetVolume

    public function Stop()
    {
        $targetInstance = $this->findTarget();

        if ($targetInstance === $this->InstanceID) {
            $ip = $this->getIP();

            SetValue($this->GetIDForIdent('Status'), 3);
            $sonos = new SonosAccess($ip);
            if ($sonos->GetTransportInfo() == 1) {
                $sonos->Stop();
            }
        } else {
            $this->SendDataToParent(json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'callFunction',
                'targetInstance' => $targetInstance,
                'function'       => 'Stop'
            ]));
        }
    } //END Stop

    // end of public Functions for End users

    public function updateStatus()
    {
        try {
            $ip = $this->getIP();
        } catch (Exception $e) {
            return;
        }

        $vidVolume = @$this->GetIDForIdent('Volume');
        $vidMute = @$this->GetIDForIdent('Mute');
        $vidNightMode = @$this->GetIDForIdent('NightMode');
        $vidDialogLevel = @$this->GetIDForIdent('DialogLevel');
        $vidLoudness = @$this->GetIDForIdent('Loudness');
        $vidBass = @$this->GetIDForIdent('Bass');
        $vidTreble = @$this->GetIDForIdent('Treble');
        $vidBalance = @$this->GetIDForIdent('Balance');
        $vidMemberOfGroup = @$this->GetIDForIdent('MemberOfGroup');
        $vidStatus = @$this->GetIDForIdent('Status');
        $vidRadio = @$this->GetIDForIdent('Radio');
        $vidSleeptimer = @$this->GetIDForIdent('Sleeptimer');
        $vidNowPlaying = @$this->GetIDForIdent('nowPlaying');
        $vidDetails = @$this->GetIDForIdent('Details');
        $vidCoverURL = @$this->GetIDForIdent('CoverURL');
        $vidStationID = @$this->GetIDForIdent('StationID');
        $vidContentStream = @$this->GetIDForIdent('ContentStream');
        $vidArtist = @$this->GetIDForIdent('Artist');
        $vidTitle = @$this->GetIDForIdent('Title');
        $vidAlbum = @$this->GetIDForIdent('Album');
        $vidTrackDuration = @$this->GetIDForIdent('TrackDuration');
        $vidPosition = @$this->GetIDForIdent('Position');
        $vidCrossfade = @$this->GetIDForIdent('Crossfade');
        $vidPlaymode = @$this->GetIDForIdent('PlayMode');
        $vidGroupVolume = @$this->GetIDForIdent('GroupVolume');

        $AlbumArtHeight = $this->ReadAttributeInteger('AlbumArtHeight');

        $sonos = new SonosAccess($ip);
        $status = $sonos->GetTransportInfo();

        SetValueInteger($vidVolume, $sonos->GetVolume());
        if ($vidMute) {
            SetValueInteger($vidMute, $sonos->GetMute());
        }
        if ($vidNightMode) {
            SetValueInteger($vidNightMode, $sonos->GetNightMode());
        }
        if ($vidDialogLevel) {
            SetValueInteger($vidDialogLevel, $sonos->GetDialogLevel());
        }
        if ($vidLoudness) {
            SetValueInteger($vidLoudness, $sonos->GetLoudness());
        }
        if ($vidBass) {
            SetValueInteger($vidBass, $sonos->GetBass());
        }
        if ($vidTreble) {
            SetValueInteger($vidTreble, $sonos->GetTreble());
        }
        if ($vidCrossfade) {
            SetValueInteger($vidCrossfade, $sonos->GetCrossfade());
        }
        if ($vidPlaymode) {
            SetValueInteger($vidPlaymode, $sonos->GetTransportsettings());
        }

        if ($vidBalance) {
            $leftVolume = $sonos->GetVolume('LF');
            $rightVolume = $sonos->GetVolume('RF');

            if ($leftVolume == $rightVolume) {
                SetValueInteger($vidBalance, 0);
            } elseif ($leftVolume > $rightVolume) {
                SetValueInteger($vidBalance, $rightVolume - 100);
            } else {
                SetValueInteger($vidBalance, 100 - $leftVolume);
            }
        }

        $MemberOfGroup = 0;
        if ($vidMemberOfGroup) {
            $MemberOfGroup = GetValueInteger($vidMemberOfGroup);
        }

        if ($MemberOfGroup) {
            // If Sonos is member of a group, use values of Group Coordinator
            SetValueInteger($vidStatus, GetValueInteger(IPS_GetObjectIDByIdent('Status', $MemberOfGroup)));
            $actuallyPlaying = GetValueString(IPS_GetObjectIDByIdent('nowPlaying', $MemberOfGroup));
            SetValueInteger($vidRadio, GetValueInteger(IPS_GetObjectIDByIdent('Radio', $MemberOfGroup)));
            if ($vidSleeptimer) {
                SetValueInteger($vidSleeptimer, @GetValueInteger(IPS_GetObjectIDByIdent('Sleeptimer', $MemberOfGroup)));
            }
            if ($vidCoverURL) {
                SetValueString($vidCoverURL, @GetValueString(IPS_GetObjectIDByIdent('CoverURL', $MemberOfGroup)));
            }
            if ($vidContentStream) {
                SetValueString($vidContentStream, @GetValueString(IPS_GetObjectIDByIdent('ContentStream', $MemberOfGroup)));
            }
            if ($vidArtist) {
                SetValueString($vidArtist, @GetValueString(IPS_GetObjectIDByIdent('Artist', $MemberOfGroup)));
            }
            if ($vidAlbum) {
                SetValueString($vidAlbum, @GetValueString(IPS_GetObjectIDByIdent('Album', $MemberOfGroup)));
            }
            if ($vidTrackDuration) {
                SetValueString($vidTrackDuration, @GetValueString(IPS_GetObjectIDByIdent('TrackDuration', $MemberOfGroup)));
            }
            if ($vidPosition) {
                SetValueString($vidPosition, @GetValueString(IPS_GetObjectIDByIdent('Position', $MemberOfGroup)));
            }
            if ($vidTitle) {
                SetValueString($vidTitle, @GetValueString(IPS_GetObjectIDByIdent('Title', $MemberOfGroup)));
            }
            if ($vidDetails) {
                SetValueString($vidDetails, @GetValueString(IPS_GetObjectIDByIdent('Details', $MemberOfGroup)));
            }
        } else {
            SetValueInteger($vidStatus, $status);

            // Titelanzeige
            $currentStation = 0;

            if ($status != 1) {
                // No title if not playing
                $actuallyPlaying = '';
            } else {
                $positionInfo = $sonos->GetPositionInfo();
                $mediaInfo = $sonos->GetMediaInfo();

                if ($positionInfo['streamContent']) {
                    $actuallyPlaying = $positionInfo['streamContent'];
                } else {
                    $actuallyPlaying = $positionInfo['title'] . ' | ' . $positionInfo['artist'];
                }

                // start find current Radio in VariableProfile
                $radioStations = json_decode($this->ReadAttributeString('RadioStations'), true);

                $playingRadioStation = '';
                foreach ($radioStations as $radioStation) {
                    if ($radioStation['URL'] == htmlspecialchars_decode($mediaInfo['CurrentURI'])) {
                        $playingRadioStation = $radioStation['name'];
                        $image = $radioStation['imageURL'];
                        break;
                    }
                }

                if ($playingRadioStation == '') {
                    foreach ((new SimpleXMLElement($sonos->BrowseContentDirectory('R:0/0')['Result']))->item as $item) {
                        if ($item->res == htmlspecialchars_decode($mediaInfo['CurrentURI'])) {
                            $playingRadioStation = (string) $item->xpath('dc:title')[0];
                            break;
                        }
                    }
                }

                if (isset($playingRadioStation)) {
                    $Associations = IPS_GetVariableProfile('SONOS.Radio')['Associations'];
                    foreach ($Associations as $key => $station) {
                        if ($station['Name'] == $playingRadioStation) {
                            $currentStation = $station['Value'];
                            break;
                        }
                    }
                }
                // end find current Radio in VariableProfile
            }
            SetValueInteger($vidRadio, $currentStation);

            // detailed Information
            if ($vidContentStream) {
                SetValueString($vidContentStream, @$positionInfo['streamContent']);
            }
            if ($vidArtist) {
                SetValueString($vidArtist, @$positionInfo['artist']);
            }
            if ($vidAlbum) {
                SetValueString($vidAlbum, @$positionInfo['album']);
            }
            if ($vidTrackDuration) {
                SetValueString($vidTrackDuration, @$positionInfo['TrackDuration']);
            }
            if ($vidPosition) {
                SetValueString($vidPosition, @$positionInfo['RelTime']);
            }
            if ($vidTitle) {
                if (@$mediaInfo['title']) {
                    SetValueString($vidTitle, @$mediaInfo['title']);
                } else {
                    SetValueString($vidTitle, @$positionInfo['title']);
                }
            }

            if ($vidDetails) {
                if (!isset($stationID)) {
                    $stationID = '';
                }
                if (isset($positionInfo)) {
                    // SPDIF and analog
                    if (preg_match('/^RINCON_/', $mediaInfo['title'])) {
                        $detailHTML = '';
                    // Radio or stream(?)
                    } elseif ($mediaInfo['title']) {
                        // get stationID if playing via TuneIn
                        $stationID = preg_replace("#(.*)x-sonosapi-stream:(.*?)\?sid(.*)#is", '$2', $mediaInfo['CurrentURI']);
                        if (!isset($image)) {
                            $image = '';
                        }
                        if ($stationID && $stationID[0] == 's') {
                            if (@GetValueString($vidStationID) == $stationID) {
                                $image = GetValueString($vidCoverURL);
                            } else {
                                $serial = substr($this->ReadPropertyString('RINCON'), 7, 12);
                                $image = preg_replace('#(.*)<LOGO>(.*?)\</LOGO>(.*)#is', '$2', @file_get_contents('http://opml.radiotime.com/Describe.ashx?c=nowplaying&id=' . $stationID . '&partnerId=IAeIhU42&serial=' . $serial));
                            }
                        } else {
                            $stationID = '';
                        }
                        $detailHTML = '<table width="100%"><tr><td><div style="text-align: right;"><div><b>' . $positionInfo['streamContent'] . '</b></div><div>&nbsp;</div><div>' . $mediaInfo['title'] . '</div></div></td>';

                        if (strlen($image) > 0) {
                            $detailHTML .= '<td width="' . $AlbumArtHeight . 'px" valign="top">
                                <div style="width: ' . $AlbumArtHeight . 'px; height: ' . $AlbumArtHeight . 'px; perspective: ' . $AlbumArtHeight . 'px; right: 0px; margin-bottom: 10px;">
                              	<img src="' . @$image . '" style="max-width: ' . $AlbumArtHeight . 'px; max-height: ' . $AlbumArtHeight . 'px; -webkit-box-reflect: below 0 -webkit-gradient(linear, left top, left bottom, from(transparent), color-stop(0.88, transparent), to(rgba(255, 255, 255, 0.5))); transform: rotateY(-10deg) translateZ(-35px);">
                              </div></td>';
                        }

                        $detailHTML .= '</tr></table>';

                    // normal files
                    } else {
                        $durationSeconds = 0;
                        $currentPositionSeconds = 0;
                        if ($positionInfo['TrackDuration'] && preg_match('/\d+:\d+:\d+/', $positionInfo['TrackDuration'])) {
                            $durationArray = explode(':', $positionInfo['TrackDuration']);
                            $currentPositionArray = explode(':', $positionInfo['RelTime']);
                            $durationSeconds = $durationArray[0] * 3600 + $durationArray[1] * 60 + $durationArray[2];
                            $currentPositionSeconds = $currentPositionArray[0] * 3600 + $currentPositionArray[1] * 60 + $currentPositionArray[2];
                        }
                        $detailHTML = '<table width="100%"><tr><td><div style="text-align: right;"><div><b>' . $positionInfo['title'] . '</b></div><div>&nbsp;</div><div>' . $positionInfo['artist'] . '</div><div>' . $positionInfo['album'] . '</div><div>&nbsp;</div><div>' . $positionInfo['RelTime'] . ' / ' . $positionInfo['TrackDuration'] . '</div></div></td>';

                        if (isset($positionInfo['albumArtURI'])) {
                            $detailHTML .= '<td width="' . $AlbumArtHeight . 'px" valign="top"><div style="width: ' . $AlbumArtHeight . 'px; height: ' . $AlbumArtHeight . 'px; perspective: ' . $AlbumArtHeight . 'px; right: 0px; margin-bottom: 10px;"><img src="' . @$positionInfo['albumArtURI'] . '" style="max-width: ' . $AlbumArtHeight . 'px; max-height: ' . $AlbumArtHeight . 'px; -webkit-box-reflect: below 0 -webkit-gradient(linear, left top, left bottom, from(transparent), color-stop(0.88, transparent), to(rgba(255, 255, 255, 0.5))); transform: rotateY(-10deg) translateZ(-35px);"></div></td>';
                        }

                        $detailHTML .= '</tr></table>';
                    }
                }
                @SetValueString($vidDetails, $detailHTML);
                if ($vidCoverURL) {
                    if ((isset($image)) && (strlen($image) > 0)) {
                        SetValueString($vidCoverURL, $image);
                    } else {
                        SetValueString($vidCoverURL, @$positionInfo['albumArtURI']);
                    }
                }
                SetValueString($vidStationID, $stationID);
            }

            // Sleeptimer
            if ($vidSleeptimer) {
                $sleeptimer = $sonos->GetSleeptimer();
                if ($sleeptimer) {
                    $SleeptimerArray = explode(':', $sonos->GetSleeptimer());
                    $SleeptimerMinutes = $SleeptimerArray[0] * 60 + $SleeptimerArray[1];
                    if ($SleeptimerArray[2]) {
                        $SleeptimerMinutes = $SleeptimerMinutes + 1;
                    }
                } else {
                    $SleeptimerMinutes = 0;
                }

                SetValueInteger($vidSleeptimer, $SleeptimerMinutes);
            }
        }

        $nowPlaying = GetValueString($vidNowPlaying);
        if ($actuallyPlaying != $nowPlaying) {
            SetValueString($vidNowPlaying, $actuallyPlaying);
        }

        // Set Group Volume
        $groupMembers = $this->ReadAttributeString('GroupMembers');
        $groupMembersArray = [];
        if ($groupMembers) {
            $groupMembersArray = array_map('intval', explode(',', $groupMembers));
        }
        $groupMembersArray[] = $this->InstanceID;

        $GroupVolume = 0;
        foreach ($groupMembersArray as $key => $ID) {
            $GroupVolume += GetValueInteger(IPS_GetObjectIDByIdent('Volume', $ID));
        }

        SetValueInteger($vidGroupVolume, intval(round($GroupVolume / count($groupMembersArray))));
    } // END UpdateStatus

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'Balance':
                $this->SetBalance($Value);
                break;
            case 'Bass':
                $this->SetBass($Value);
                break;
            case 'Crossfade':
                $this->SetCrossfade($Value);
                break;
            case 'GroupVolume':
                $this->SetGroupVolume($Value);
                break;
            case 'Loudness':
                $this->SetLoudness($Value);
                break;
            case 'NightMode':
                $this->SetNightMode($Value);
                break;
            case 'DialogLevel':
                $this->SetDialogLevel($Value);
                break;
            case 'MemberOfGroup':
                $this->SetGroup($Value);
                break;
            case 'Mute':
                $this->SetMute($Value);
                break;
            case 'PlayMode':
                $this->SetPlayMode($Value);
                break;
            case 'Playlist':
                $this->SetPlaylist(IPS_GetVariableProfile('SONOS.Playlist')['Associations'][$Value - 1]['Name']);
                SetValue($this->GetIDForIdent($Ident), $Value);
                $this->Play();
                sleep(1);
                SetValue($this->GetIDForIdent($Ident), 0);
                break;
            case 'Radio':
                $this->SetRadio(IPS_GetVariableProfile('SONOS.Radio')['Associations'][$Value - 1]['Name']);
                SetValue($this->GetIDForIdent($Ident), $Value);
                $this->Play();
                break;
            case 'Status':
                switch ($Value) {
                    case 0: //Prev
                        $this->Previous();
                        break;
                    case 1: //Play
                        $this->Play();
                        break;
                    case 2: //Pause
                        $this->Pause();
                        break;
                    case 3: //Stop
                        $this->Stop();
                        break;
                    case 4: //Next
                        $this->Next();
                        break;
                }
                break;
            case 'Treble':
                $this->SetTreble($Value);
                break;
            case 'Volume':
                $this->SetVolume($Value);
                break;
            default:
                throw new Exception('Invalid ident');
        }
    }    // END RequestAction

    public function getRINCON(string $ip)
    {
        if ($ip) {
            $this->UpdateFormField('rinconMessage', 'visible', false);
            $ipAddress = gethostbyname($ip);
        } else {
            return;
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL            => 'http://' . $ipAddress . ':1400/xml/device_description.xml'
        ]);

        $result = curl_exec($curl);

        if (!$result) {
            $this->UpdateFormField('rinconMessage', 'visible', true);
            $this->UpdateFormField('rinconMessage', 'caption', 'Could not connect to "' . $ip . '"');
            return;
        }

        $xmlr = new SimpleXMLElement($result);
        $rincon = str_replace('uuid:', '', $xmlr->device->UDN);
        if ($rincon) {
            $this->UpdateFormField('RINCON', 'value', $rincon);
            $this->UpdateFormField('rinconButton', 'visible', false);
        } else {
            $this->UpdateFormField('rinconMessage', 'visible', true);
            $this->UpdateFormField('rinconMessage', 'caption', 'RINCON could not be read from "' . $ip . '"');
        }
    }

    // internal functions
    private function alexa_get_value($variableName, $type, &$response)
    {
        $vid = @$this->GetIDForIdent($variableName);
        if ($vid) {
            switch ($type) {
                case 'string':
                    $response[$variableName] = strval(GetValue($vid));
                    break;
                case 'fromatted':
                    $response[$variableName] = GetValueFormatted($vid);
                    break;
            }
        } else {
            $response[$variableName] = 'not configured';
        }
    }

    private function getIP()
    {
        if ($this->ReadAttributeBoolean('Vanished')) {
            throw new Exception($this->Translate('Sonos Player is currently marked as "vanished" in Sonos. Maybe switched off?!'));
        }

        $ipSetting = $this->ReadPropertyString('IPAddress');
        $timeout = $this->ReadPropertyInteger('TimeOut');
        $ip = '';

        if ($ipSetting) {
            $ip = gethostbyname($ipSetting);
            if ($timeout && Sys_Ping($ip, $timeout) != true) {
                if (Sys_Ping($ip, $timeout) != true) {
                    throw new Exception(sprintf($this->Translate('Sonos Player %s is not available, TimeOut: %s ms'), $ipSetting, $timeout));
                }
            }
        }
        return $ip;
    } // End getIP

    private function findTarget()
    {
        if ($this->ReadAttributeBoolean('Vanished')) {
            throw new Exception($this->Translate('Sonos Player is currently marked as "vanished" in Sonos. Maybe switched off?!'));
        }

        // instance is a coordinator and can execute command
        if ($this->ReadAttributeBoolean('Coordinator') === true) {
            return $this->InstanceID;
        }

        $memberOfGroup = GetValueInteger($this->GetIDForIdent('MemberOfGroup'));
        if ($memberOfGroup) {
            return $memberOfGroup;
        }
        throw new Exception($this->Translate('Instance is not a coordinator and group coordinator could not be determined'));
    } // End findTarget

    private function getRadioURL(string $name): string
    {
        $radioStations = json_decode($this->ReadAttributeString('RadioStations'), true);
        $foundStation = [];
        foreach ($radioStations as $radioStation) {
            if ($radioStation['name'] == $name) {
                $foundStation = $radioStation;
                break;
            }
        }
        if (!$foundStation) {
            throw new Exception(sprintf($this->Translate('Radio station "%s" not found'), $name));
        }
        return $foundStation['URL'];
    } // End getRadio

    private function checkPlaylistAction()
    {
        $Associations = IPS_GetVariableProfile('SONOS.Playlist')['Associations'];
        if (isset($Associations[0])) {
            if ($Associations[0]['Value'] == 0) {
                $this->DisableAction('Playlist');
            } else {
                $this->EnableAction('Playlist');
            }
        } else {
            $this->DisableAction('Playlist');
        }
    }

    private function getPositions(): array
    {
        return [
            'MemberOfGroup'   => 12,
            'GroupVolume'     => 13,
            'Details'         => 20,
            'CoverURL'        => 21,
            'ContentStream'   => 22,
            'Artist'          => 23,
            'Title'           => 24,
            'Album'           => 25,
            'TrackDuration'   => 26,
            'Position'        => 27,
            'StationID'       => 28,
            'nowPlaying'      => 29,
            'Radio'           => 40,
            'Playlist'        => 41,
            'Status'          => 49,
            'Volume'          => 50,
            'Mute'            => 51,
            'Loudness'        => 52,
            'Bass'            => 53,
            'Treble'          => 54,
            'Balance'         => 58,
            'Sleeptimer'      => 60,
            'PlayMode'        => 61,
            'Crossfade'       => 62,
            'NightMode'       => 63,
            'DialogLevel'     => 64
        ];
    }
}
