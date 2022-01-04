<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/sonosAccess.php'; // SOAP Access to Sonos
require_once __DIR__ . '/../libs/VariableProfile.php';
require_once __DIR__ . '/../libs/CommonFunctions.php';
require_once __DIR__ . '/getSonos.php';

class SonosPlayer extends IPSModule
{
    use VariableProfile;
    use CommonFunctions;
    use GetSonos;

    public function Create()
    {
        parent::Create();

        $this->ConnectParent('{27B601A0-6EA4-89E3-27AD-2D902307BD8C}'); // Connect To Splitter
        // listen for JSON strings containg own InstanceID, null or an array of Instance IDs including own InstanceID in filed targetInstance
        $this->SetReceiveDataFilter('.*"targetInstance":(' . $this->InstanceID . '|null|\[[\d,]*' . $this->InstanceID . '[\d,]*\]).*');

        $this->RegisterPropertyString('IPAddress', '');
        $this->RegisterPropertyString('RINCON', '');
        $this->RegisterPropertyString('Model', '');
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
        $this->RegisterPropertyBoolean('DisableHiding', false);

        $this->RegisterAttributeBoolean('Coordinator', false);
        $this->RegisterAttributeString('GroupMembers', '');
        $this->RegisterAttributeBoolean('Vanished', false);
        $this->RegisterAttributeBoolean('OutputFixed', false);

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

        $Summary = $this->ReadPropertyString('IPAddress');
        $Model = $this->ReadPropertyString('Model');
        if ($Model) {
            $Summary .= ' (' . $Model . ')';
        }
        $this->SetSummary($Summary);

        // Set status to "Instanz ist aktiv"
        $this->SetStatus(102);

        // 1) general availabe
        $positions = $this->getPositions();
        if (!@$this->GetIDForIdent('MemberOfGroup')) {
            $this->RegisterVariableInteger('MemberOfGroup', $this->Translate('Member of group'), 'SONOS.Groups', $positions['MemberOfGroup']);
            $this->EnableAction('MemberOfGroup');
        }
        if (!@$this->GetIDForIdent('GroupVolume')) {
            $this->RegisterVariableInteger('GroupVolume', $this->Translate('Group volume'), 'SONOS.Volume', $positions['GroupVolume']);
            $this->EnableAction('GroupVolume');
        }
        if (!@$this->GetIDForIdent('nowPlaying')) {
            $this->RegisterVariableString('nowPlaying', $this->Translate('now Playing'), '', $positions['nowPlaying']);
        }
        if (!@$this->GetIDForIdent('Radio')) {
            $this->RegisterVariableInteger('Radio', $this->Translate('Radio'), 'SONOS.Radio', $positions['Radio']);
            $this->EnableAction('Radio');
        }
        if (!@$this->GetIDForIdent('Status')) {
            $this->RegisterVariableInteger('Status', $this->Translate('Status'), 'SONOS.Status', $positions['Status']);
            $this->EnableAction('Status');
        }
        if (!@$this->GetIDForIdent('Volume')) {
            $this->RegisterVariableInteger('Volume', $this->Translate('Volume'), 'SONOS.Volume', $positions['Volume']);
            $this->EnableAction('Volume');
        }
        if (!@$this->GetIDForIdent('Playlist')) {
            $this->RegisterVariableInteger('Playlist', $this->Translate('Playlist'), 'SONOS.Playlist', $positions['Playlist']);
        }

        $this->checkPlaylistAction();

        if ($Model == 'Move' || $Model == 'Roam') {
            if (!@$this->GetIDForIdent('Battery')) {
                $this->RegisterVariableInteger('Battery', $this->Translate('Battery'), '~Battery.100', $positions['Battery']);
            }
            if (!@$this->GetIDForIdent('PowerSource')) {
                $this->RegisterVariableInteger('PowerSource', $this->Translate('PowerSource'), 'SONOS.PowerSource', $positions['PowerSource']);
            }
        }

        // 2) Add/Remove according to feature activation

        // Bass
        if ($this->ReadPropertyBoolean('BassControl')) {
            if (!@$this->GetIDForIdent('Bass')) {
                $this->RegisterVariableInteger('Bass', $this->Translate('Bass'), 'SONOS.Tone', $positions['Bass']);
                $this->EnableAction('Bass');
            }
        } else {
            $this->removeVariableAction('Bass');
        }

        // Treble
        if ($this->ReadPropertyBoolean('TrebleControl')) {
            if (!@$this->GetIDForIdent('Treble')) {
                $this->RegisterVariableInteger('Treble', $this->Translate('Treble'), 'SONOS.Tone', $positions['Treble']);
                $this->EnableAction('Treble');
            }
        } else {
            $this->removeVariableAction('Treble');
        }

        // Mute
        if ($this->ReadPropertyBoolean('MuteControl')) {
            if (!@$this->GetIDForIdent('Mute')) {
                $this->RegisterVariableBoolean('Mute', $this->Translate('Mute'), 'SONOS.Switch', $positions['Mute']);
                $this->EnableAction('Mute');
            }
        } else {
            $this->removeVariableAction('Mute');
        }

        // Loudness
        if ($this->ReadPropertyBoolean('LoudnessControl')) {
            if (!@$this->GetIDForIdent('Loudness')) {
                $this->RegisterVariableBoolean('Loudness', $this->Translate('Loudness'), 'SONOS.Switch', $positions['Loudness']);
                $this->EnableAction('Loudness');
            }
        } else {
            $this->removeVariableAction('Loudness');
        }

        // NightMode
        if ($this->ReadPropertyBoolean('NightModeControl')) {
            if ($Model == 'Playbar' || $Model == 'Playbase' || $Model == 'Beam' || $Model == 'Arc' || $Model == '') {
                if (!@$this->GetIDForIdent('NightMode')) {
                    $this->RegisterVariableBoolean('NightMode', $this->Translate('Night Mode'), 'SONOS.Switch', $positions['NightMode']);
                    $this->EnableAction('NightMode');
                }
                if (!@$this->GetIDForIdent('DialogLevel')) {
                    $this->RegisterVariableBoolean('DialogLevel', $this->Translate('Dialog Level'), 'SONOS.Switch', $positions['DialogLevel']);
                    $this->EnableAction('DialogLevel');
                }
            } else {
                // Set status to display that  night mode is not supported
                $this->SetStatus(201);
            }
        } else {
            $this->removeVariableAction('NightMode');
            $this->removeVariableAction('DialogLevel');
        }
        // Balance
        if ($this->ReadPropertyBoolean('BalanceControl')) {
            if (!@$this->GetIDForIdent('Balance')) {
                $this->RegisterVariableInteger('Balance', $this->Translate('Balance'), 'SONOS.Balance', $positions['Balance']);
                $this->EnableAction('Balance');
            }
        } else {
            $this->removeVariableAction('Balance');
        }

        // Sleeptimer
        if ($this->ReadPropertyBoolean('SleeptimerControl')) {
            if (!@$this->GetIDForIdent('Sleeptimer')) {
                $this->RegisterVariableInteger('Sleeptimer', $this->Translate('Sleeptimer'), '', $positions['Sleeptimer']);
            }
        } else {
            $this->removeVariable('Sleeptimer');
        }

        // PlayMode + Crossfade
        if ($this->ReadPropertyBoolean('PlayModeControl')) {
            if (!@$this->GetIDForIdent('PlayMode')) {
                $this->RegisterVariableInteger('PlayMode', $this->Translate('Play Mode'), 'SONOS.PlayMode', $positions['PlayMode']);
                $this->EnableAction('PlayMode');
            }
            if (!@$this->GetIDForIdent('Crossfade')) {
                $this->RegisterVariableBoolean('Crossfade', $this->Translate('Crossfade'), 'SONOS.Switch', $positions['Crossfade']);
                $this->EnableAction('Crossfade');
            }
        } else {
            $this->removeVariableAction('PlayMode');
            $this->removeVariableAction('Crossfade');
        }

        // Detailed Now Playing informtion
        if ($this->ReadPropertyBoolean('DetailedInformation')) {
            if (!@$this->GetIDForIdent('Details')) {
                $this->RegisterVariableString('Details', $this->Translate('Details'), '~HTMLBox', $positions['Details']);
            }
            if (!@$this->GetIDForIdent('CoverURL')) {
                IPS_SetHidden($this->RegisterVariableString('CoverURL', $this->Translate('Cover URL'), '', $positions['CoverURL']), true);
            }
            if (!@$this->GetIDForIdent('ContentStream')) {
                IPS_SetHidden($this->RegisterVariableString('ContentStream', $this->Translate('Content Stream'), '', $positions['ContentStream']), true);
            }
            if (!@$this->GetIDForIdent('Artist')) {
                IPS_SetHidden($this->RegisterVariableString('Artist', $this->Translate('Artist'), '', $positions['Artist']), true);
            }
            if (!@$this->GetIDForIdent('Title')) {
                IPS_SetHidden($this->RegisterVariableString('Title', $this->Translate('Title'), '', $positions['Title']), true);
            }
            if (!@$this->GetIDForIdent('Album')) {
                IPS_SetHidden($this->RegisterVariableString('Album', $this->Translate('Album'), '', $positions['Album']), true);
            }
            if (!@$this->GetIDForIdent('TrackDuration')) {
                IPS_SetHidden($this->RegisterVariableString('TrackDuration', $this->Translate('Track Duration'), '', $positions['TrackDuration']), true);
            }
            if (!@$this->GetIDForIdent('Position')) {
                IPS_SetHidden($this->RegisterVariableString('Position', $this->Translate('Position'), '', $positions['Position']), true);
            }
            if (!@$this->GetIDForIdent('StationID')) {
                IPS_SetHidden($this->RegisterVariableString('StationID', $this->Translate('Station ID'), '', $positions['StationID']), true);
                // in version 1 of sonos StationID was cleared once an hour, but not sure why. Lets see...
            }
            if (!@$this->GetIDForIdent('Track')) {
                IPS_SetHidden($this->RegisterVariableInteger('Track', $this->Translate('Track'), '', $positions['Track']), true);
            }
            // Also create a Media File for the cover
            $MediaID = @$this->GetIDForIdent('Cover');
            if (!$MediaID) {
                $MediaID = IPS_CreateMedia(1);
                IPS_SetParent($MediaID, $this->InstanceID);
                IPS_SetIdent($MediaID, 'Cover');
                IPS_SetMediaCached($MediaID, true);
                $ImageFile = IPS_GetKernelDir() . 'media' . DIRECTORY_SEPARATOR . $MediaID . '.jpg';
                IPS_SetMediaFile($MediaID, $ImageFile, false);
                IPS_SetName($MediaID, $this->Translate('Cover'));
                IPS_SetInfo($MediaID, $this->Translate('Cover'));
            }
            IPS_SetMediaContent($MediaID, 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='); // transparent picture
            IPS_SendMediaEvent($MediaID);
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
            $MediaID = @$this->GetIDForIdent('Cover');
            if ($MediaID && IPS_MediaExists($MediaID)) {
                IPS_DeleteMedia($MediaID, true);
            }
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
        if ($this->ReadPropertyString('IPAddress')) {
            $showRINCONMessage = false;
            $showModelMessage = false;
        } else {
            $showRINCONMessage = true;
            $showModelMessage = true;
        }

        $knownModels = [
            ['caption' => 'Arc',         'value' => 'Arc'],
            ['caption' => 'Amp',         'value' => 'Amp'],
            ['caption' => 'Beam',        'value' => 'Beam'],
            ['caption' => 'Connect',     'value' => 'Connect'],
            ['caption' => 'Connect:Amp', 'value' => 'Connect:Amp'],
            ['caption' => 'Move',        'value' => 'Move'],
            ['caption' => 'One',         'value' => 'One'],
            ['caption' => 'One SL',      'value' => 'One SL'],
            ['caption' => 'Play:1',      'value' => 'Play:1'],
            ['caption' => 'Play:3',      'value' => 'Play:3'],
            ['caption' => 'Play:5',      'value' => 'Play:5'],
            ['caption' => 'Playbar',     'value' => 'Playbar'],
            ['caption' => 'Playbase',    'value' => 'Playbase'],
            ['caption' => 'Roam',        'value' => 'Roam'],
            ['caption' => 'SYMFONISK',   'value' => 'SYMFONISK']
        ];

        // in case a model in unknown, but handed in via discovery, be tolerant
        $ModelKnown = false;

        $Model = $this->ReadPropertyString('Model');

        foreach ($knownModels as $key => $knownModel) {
            if ($knownModel['caption'] === $Model) {
                $ModelKnown = true;
                break;
            }
        }

        if ($ModelKnown === false) {
            $knownModels[] = ['caption' => $Model, 'value' => $Model];
        }

        // hide NightMode on unsupported devices
        $NightMode = $this->ReadPropertyBoolean('NightModeControl');
        if ($Model == 'Playbar' || $Model == 'Playbase' || $Model == 'Beam' || $Model == 'Arc' || $NightMode === true) {
            $showNightMode = true;
        } else {
            $showNightMode = false;
        }

        $Form = [
            'status'      => [
                ['code'=> 201, 'icon'=> 'error', 'caption'=> sprintf($this->Translate('Night Mode not supported on model %s'), $Model)]
            ],
            'elements'    => [
                ['name' => 'IPAddress',             'type' => 'ValidationTextBox', 'caption' => 'IP-Address/Host'],
                [
                    'type' => 'RowLayout', 'items' => [
                        ['name' => 'RINCON',        'type' => 'ValidationTextBox', 'caption' => 'RINCON',      'validate' => 'RINCON_[\dA-F]{12}01400'],
                        ['name' => 'rinconButton',  'type' => 'Button',            'caption' => 'read RINCON', 'onClick'  => 'SNS_getRINCON($id,$IPAddress);'],
                        ['name' => 'rinconMessage', 'type' => 'Label',             'caption' => 'RINCON can be read automatically, once IP-Address/Host was entered', 'visible'  => $showRINCONMessage]
                    ]
                ],
                [
                    'type' => 'RowLayout', 'items' => [
                        ['name' => 'Model',        'type' => 'Select',            'caption' => 'Model', 'options'  => $knownModels],
                        ['name' => 'modelButton',  'type' => 'Button',            'caption' => 'read Model', 'onClick'  => 'SNS_getModel($id,$IPAddress);'],
                        ['name' => 'modelMessage', 'type' => 'Label',             'caption' => 'Model can be read automatically, once IP-Address/Host was entered', 'visible'  => $showModelMessage]
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
                ['name' => 'NightModeControl',      'type' => 'CheckBox',          'caption' => 'NightMode Control', 'visible' => $showNightMode],
                ['name' => 'DetailedInformation',   'type' => 'CheckBox',          'caption' => 'detailed info'],
                ['name' => 'ForceOrder',            'type' => 'CheckBox',          'caption' => 'Force Variable order'],
                ['name' => 'DisableHiding',         'type' => 'CheckBox',          'caption' => 'Disable automatic hiding of variables']
            ]];
        return json_encode($Form);
    }

    public function ReceiveData($JSONstring)
    {
        $input = json_decode($JSONstring, true);

        if ($input['type'] != 'getVariableNoDebug' && $input['type'] != 'getCoordinatorValues' && $input['type'] != 'getProperties') {
            $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$JSONstring=%s', $JSONstring), 0);
        }

        switch ($input['type']) {
            case 'grouping':
                if ($input['data']['vanished']) {
                    $this->SendDebug(__FUNCTION__ . '->grouping', 'marking instance as vanished', 0);
                    $this->WriteAttributeBoolean('Vanished', true); // Not available according to SONOS
                    @IPS_SetVariableProfileAssociation('SONOS.Groups', $this->InstanceID, '', '', -1);  // cannot be selected as Group
                    IPS_SetHidden($this->InstanceID, true); // cannot be used, therefore hiding it
                    return;
                }

                $memberOfGroupID = $this->GetIDForIdent('MemberOfGroup');

                if ($this->ReadAttributeBoolean('Vanished')) {
                    $this->SendDebug(__FUNCTION__ . '->grouping', 'came back after being vanished', 0);
                    $this->WriteAttributeBoolean('Vanished', false);
                    IPS_SetHidden($this->InstanceID, false);
                    if ($this->ReadPropertyBoolean('RejoinGroup')) {
                        $currentGroup = GetValueInteger($memberOfGroupID);
                        if ($currentGroup != 0 && $input['data']['Coordinator'] == 0) {
                            $this->SendDebug(__FUNCTION__ . '->grouping', 'trying to rejoin group', 0);
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
                    if (!$this->ReadPropertyBoolean('DisableHiding')) {
                        @IPS_SetHidden($this->GetIDForIdent('nowPlaying'), $hidden);
                        @IPS_SetHidden($this->GetIDForIdent('Radio'), $hidden);
                        @IPS_SetHidden($this->GetIDForIdent('Playlist'), $hidden);
                        @IPS_SetHidden($this->GetIDForIdent('PlayMode'), $hidden);
                        @IPS_SetHidden($this->GetIDForIdent('Crossfade'), $hidden);
                        @IPS_SetHidden($this->GetIDForIdent('Status'), $hidden);
                        @IPS_SetHidden($this->GetIDForIdent('Sleeptimer'), $hidden);
                        @IPS_SetHidden($this->GetIDForIdent('Details'), $hidden);
                    }
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
            case 'prepareAllPlayGrouping':
                $this->SendDebug(__FUNCTION__ . '->prepareAllPlayGrouping: clear buffer', '', 0);
                $this->SetBuffer($this->InstanceID . 'PlayFilesGrouping', '');

                if (array_search($this->InstanceID, $input['involvedInstances']) === false && array_search(GetValue($this->GetIDForIdent('MemberOfGroup')), $input['involvedInstances']) === false) {
                    $this->SendDebug(__FUNCTION__ . '->prepareAllPlayGrouping', 'Player is not involved, do not touch', 0);
                    return;
                }

                try {
                    $sonos = $this->getSonosAccess();
                } catch (Exception $e) {
                    $this->SendDebug(__FUNCTION__ . '->prepareAllPlayGrouping', 'Player is not available', 0);
                    return; // player is not available, skip
                }

                // remember settings in all players
                $Settings = [];
                $Settings['mediaInfo'] = $sonos->GetMediaInfo();
                $Settings['positionInfo'] = $sonos->GetPositionInfo();
                $Settings['transportInfo'] = $sonos->GetTransportInfo();
                $Settings['volume'] = $sonos->GetVolume();
                $Settings['mute'] = $sonos->GetMute();

                // pause all players
                if ($Settings['transportInfo'] == 1) {
                    try {
                        $this->SendDebug(__FUNCTION__ . '->prepareAllPlayGrouping->sonos', 'Pause()', 0);
                        $sonos->Pause();
                    } catch (Exception $e) {
                        if ($e->getMessage() != 'Error during Soap Call: UPnPError s:Client 701 (ERROR_AV_UPNP_AVT_INVALID_TRANSITION)') {
                            // INVALID_TRANSITION happens e.g. when still member of a group
                            throw $e;
                        }
                    }
                }

                // ungroup all players
                $this->SendDebug(__FUNCTION__ . '->prepareAllPlayGrouping->sonos', 'SetAVTransportURI(\' \')', 0);
                $sonos->SetAVTransportURI('');
                // write Buffer
                $buffer = json_encode($Settings);
                $this->SendDebug(__FUNCTION__ . '->prepareAllPlayGrouping: write buffer', $buffer, 0);
                $this->SetBuffer($this->InstanceID . 'PlayFilesGrouping', $buffer);
                break;
            case 'preparePlayGrouping':
                $buffer = $this->GetBuffer($this->InstanceID . 'PlayFilesGrouping');
                $this->SendDebug(__FUNCTION__ . '->preparePlayGrouping: read buffer', $buffer, 0);
                if ($buffer == '') {
                    return; // no prepare done, so it will not be used for playing
                }
                $Settings = json_decode($buffer, true);

                try {
                    $sonos = $this->getSonosAccess();
                } catch (Exception $e) {
                    $this->SendDebug(__FUNCTION__ . '->preparePlayGrouping', 'Player is not available', 0);
                    return; // player is not available, skip
                }

                // Set Volume
                if ($this->ReadAttributeBoolean('OutputFixed') == false) {
                    if ($input['volumeChange'] != 0) {
                        // volume request absolte or relative?
                        if ($input['volumeChange'][0] == '+' || $input['volumeChange'][0] == '-') {
                            $newVolume = ($Settings['volume'] + $input['volumeChange']);
                        } else {
                            $newVolume = $input['volumeChange'];
                        }

                        if ($newVolume > 100) {
                            $newVolume = 100;
                        } elseif ($newVolume < 0) {
                            $newVolume = 0;
                        }

                        $this->SendDebug(__FUNCTION__ . '->preparePlayGrouping->sonos', sprintf('SetVolume(%d)', (string) $newVolume), 0);
                        $sonos->SetVolume($newVolume);
                    }
                }

                // set source
                $this->SendDebug(__FUNCTION__ . '->preparePlayGrouping->sonos', sprintf('SetAVTransportURI(%s)', (string) $input['transportURI']), 0);
                $sonos->SetAVTransportURI($input['transportURI']);
                // unmute
                if ($Settings['mute']) {
                    $this->SendDebug(__FUNCTION__ . '->preparePlayGrouping->sonos', 'SetMute(false)', 0);
                    $sonos->SetMute(false);
                }
                break;
            case 'preResetPlayGrouping':
                try {
                    $sonos = $this->getSonosAccess();
                } catch (Exception $e) {
                    $this->SendDebug(__FUNCTION__ . '->preResetAllPlayGrouping', 'Player is not available', 0);
                    return; // player is not available, skip
                }

                $this->SendDebug(__FUNCTION__ . '->preRestPlayGrouping->sonos', 'SetAVTransportURI(\'\')', 0);
                $sonos->SetAVTransportURI('');
                break;
            case 'resetPlayGrouping':
                $buffer = $this->GetBuffer($this->InstanceID . 'PlayFilesGrouping');
                $this->SendDebug(__FUNCTION__ . '->resetPlayGrouping: read buffer', $buffer, 0);
                if ($buffer == '') {
                    return; // no prepare done, so no reset will be done.
                }
                $Settings = json_decode($buffer, true);

                try {
                    $sonos = $this->getSonosAccess();
                } catch (Exception $e) {
                    $this->SendDebug(__FUNCTION__ . '->resetAllPlayGrouping', 'Player is not available', 0);
                    return; // player is not available, skip
                }

                $this->SendDebug(__FUNCTION__ . '->resetPlayGrouping->sonos', sprintf('SetAVTransportURI(%s,%s)', $Settings['mediaInfo']['CurrentURI'], $Settings['mediaInfo']['CurrentURIMetaData']), 0);
                $sonos->SetAVTransportURI($Settings['mediaInfo']['CurrentURI'], $Settings['mediaInfo']['CurrentURIMetaData']);
                if (@$Settings['mediaInfo']['Track'] > 1) {
                    try {
                        $this->SendDebug(__FUNCTION__ . '->resetPlayGrouping->sonos', sprintf('Seek(\'TRACK_NR\', %s)', $Settings['mediaInfo']['Track']), 0);
                        $sonos->Seek('TRACK_NR', $Settings['mediaInfo']['Track']);
                    } catch (Exception $e) {
                    }
                }
                if ($Settings['positionInfo']['TrackDuration'] != '0:00:00' && $Settings['positionInfo']['RelTime'] != 'NOT_IMPLEMENTED') {
                    try {
                        $this->SendDebug(__FUNCTION__ . '->resetPlayGrouping->sonos', sprintf('Seek(\'REL_TIME\', %s)', $Settings['positionInfo']['RelTime']), 0);
                        $sonos->Seek('REL_TIME', $Settings['positionInfo']['RelTime']);
                    } catch (Exception $e) {
                    }
                }

                if ($this->ReadAttributeBoolean('OutputFixed') == false) {
                    $this->SendDebug(__FUNCTION__ . '->resetPlayGrouping->sonos', sprintf('SetVolume(%d)', $Settings['volume']), 0);
                    $sonos->SetVolume($Settings['volume']);
                }

                if ($Settings['mute']) {
                    $this->SendDebug(__FUNCTION__ . '->resetPlayGrouping->sonos', 'SetMute(true)', 0);
                    $sonos->SetMute(true);
                }

                // play again
                if ($Settings['transportInfo'] == 1) {
                    try {
                        $this->SendDebug(__FUNCTION__ . '->resetPlayGrouping->sonos', 'Play()', 0);
                        $sonos->Play();
                        $this->SendDebug(__FUNCTION__, 'waiting until it is really playing...', 0);
                        for ($i = 0; $i < 10; $i++) {
                            $transportInfo = $sonos->GetTransportInfo();
                            if ($transportInfo !== 1) {
                                IPS_Sleep(200);
                            } else {
                                $this->SendDebug(__FUNCTION__, 'done, now it is playing.', 0);
                                break;
                            }
                        }
                    } catch (Exception $e) {
                        if ($e->getMessage() != 'Error during Soap Call: UPnPError s:Client 701 (ERROR_AV_UPNP_AVT_INVALID_TRANSITION)') {
                            // INVALID_TRANSITION happens e.g. when no resource set
                            throw $e;
                        } else {
                            $this->SendDebug(__FUNCTION__ . ': caught exception', $e->getMessage(), 0);
                        }
                    }
                }

                $this->SendDebug(__FUNCTION__ . '->resetPlayGrouping: clear buffer', '', 0);
                $this->SetBuffer($this->InstanceID . 'PlayFilesGrouping', '');
                break;
            case 'becomeNewGroupCoordinator':

                @IPS_SetVariableProfileAssociation('SONOS.Groups', $this->InstanceID, IPS_GetName($this->InstanceID), '', -1);

                // Variablen anzeigen und verstecken.
                @IPS_SetHidden($this->GetIDForIdent('GroupVolume'), false);
                @IPS_SetHidden($this->GetIDForIdent('MemberOfGroup'), true);
                if (!$this->ReadPropertyBoolean('DisableHiding')) {
                    @IPS_SetHidden($this->GetIDForIdent('nowPlaying'), false);
                    @IPS_SetHidden($this->GetIDForIdent('Radio'), false);
                    @IPS_SetHidden($this->GetIDForIdent('Playlist'), false);
                    @IPS_SetHidden($this->GetIDForIdent('PlayMode'), false);
                    @IPS_SetHidden($this->GetIDForIdent('Crossfade'), false);
                    @IPS_SetHidden($this->GetIDForIdent('Status'), false);
                    @IPS_SetHidden($this->GetIDForIdent('Sleeptimer'), false);
                    @IPS_SetHidden($this->GetIDForIdent('Details'), false);
                }
                break;
            case 'callFunction':
                switch ($input['function']) {
                    case 'ChangeVolume':
                        $this->ChangeVolume($input['volume']);
                        break;
                    case 'DelegateGroupCoordinationTo':
                        $this->DelegateGroupCoordinationTo($input['newGroupCoordinator'], $input['rejoinGroup']);
                        break;
                    case 'DeleteSleepTimer':
                        $this->DeleteSleepTimer();
                        break;
                    case 'Next':
                        $this->Next();
                        break;
                    case 'Pause':
                        try {
                            $this->PauseInternal(false);
                        } catch (Exception $e) {
                            $this->SendDebug(__FUNCTION__ . ': caught exception', $e->getMessage(), 0);
                            // ignore exceptions, so PauseAll works fine
                        }
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
                    case 'SetMute':
                        $this->SetMute($input['mute']);
                        break;
                    case 'SetPlayMode':
                        $this->SetPlayMode($input['playmode']);
                        break;
                    case 'SetSleepTimer':
                        $this->SetSleepTimer($input['sleeptimer']);
                        break;
                    case 'SetSleepTimer':
                        $this->SetTrack($input['track']);
                        break;
                    case 'SetVolume':
                        $this->SetVolume($input['volume']);
                        break;
                    case 'Stop':
                        try {
                            $this->StopInternal(false);
                        } catch (Exception $e) {
                            $this->SendDebug(__FUNCTION__ . ': caught exception', $e->getMessage(), 0);
                            // ignore exceptions, so StopAll works fine
                        }
                        break;
                }
                break;
            case 'removeMember':
                $currentMembers = explode(',', $this->ReadAttributeString('GroupMembers'));
                $currentMembers = array_filter($currentMembers, function ($v)
                {
                    return $v != '';
                });
                $currentMembers = array_filter($currentMembers, function ($v) use ($input)
                {
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
                $currentMembers = array_filter($currentMembers, function ($v)
                {
                    return $v != '';
                });
                $currentMembers = array_filter($currentMembers, function ($v) use ($input)
                {
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
            case 'getVariableNoDebug':
            case 'getVariable':
                $vid = @$this->GetIDForIdent($input['variableIdent']);
                if ($vid) {
                    switch ($input['variableType']) {
                        case 'int':
                            $result = [
                                'instanceID'    => $this->InstanceID,
                                'variableIdent' => $input['variableIdent'],
                                'variableValue' => GetValueInteger($vid)
                            ];
                            return json_encode($result);
                            break;
                    }
                }
                break;
            case 'getName':
                $result = [
                    'instanceID'        => $this->InstanceID,
                    'name'              => IPS_GetName($this->InstanceID)
                ];
                return json_encode($result);
                break;
            case 'getCoordinatorValues':
                if (!$this->ReadAttributeBoolean('Coordinator')) {
                    $this->SendDebug(__FUNCTION__ . '->GetCoordinatorValues', 'not a Coordinator', 0);
                    return;
                }

                $vidSleeptimer = @$this->GetIDForIdent('Sleeptimer');
                $vidCoverURL = @$this->GetIDForIdent('CoverURL');
                $vidContentStream = @$this->GetIDForIdent('ContentStream');
                $vidArtist = @$this->GetIDForIdent('Artist');
                $vidAlbum = @$this->GetIDForIdent('Album');
                $vidTrackDuration = @$this->GetIDForIdent('TrackDuration');
                $vidPosition = @$this->GetIDForIdent('Position');
                $vidTitle = @$this->GetIDForIdent('Title');
                $vidTrack = @$this->GetIDForIdent('Track');
                $vidDetails = @$this->GetIDForIdent('Details');

                if ($vidSleeptimer) {
                    $sleeptimer = @GetValueInteger($vidSleeptimer);
                } else {
                    $sleeptimer = 0;
                }
                if ($vidCoverURL) {
                    $coverURL = GetValueString($vidCoverURL);
                } else {
                    $coverURL = '';
                }
                if ($vidContentStream) {
                    $contentStram = GetValueString($vidContentStream);
                } else {
                    $contentStram = '';
                }
                if ($vidArtist) {
                    $artist = GetValueString($vidArtist);
                } else {
                    $artist = '';
                }
                if ($vidAlbum) {
                    $album = GetValueString($vidAlbum);
                } else {
                    $album = '';
                }
                if ($vidTrackDuration) {
                    $trackDuration = GetValueString($vidTrackDuration);
                } else {
                    $trackDuration = '';
                }
                if ($vidPosition) {
                    $position = GetValueString($vidPosition);
                } else {
                    $position = '';
                }
                if ($vidTitle) {
                    $title = GetValueString($vidTitle);
                } else {
                    $title = '';
                }
                if ($vidTrack) {
                    $track = @GetValueInteger($vidTrack);
                } else {
                    $track = 0;
                }
                if ($vidDetails) {
                    $details = GetValueString($vidDetails);
                } else {
                    $details = '';
                }

                $result = [
                    'instanceID'    => $this->InstanceID,
                    'Status'        => GetValueInteger(@$this->GetIDForIdent('Status')),
                    'nowPlaying'    => GetValueString(@$this->GetIDForIdent('nowPlaying')),
                    'Radio'         => GetValueInteger(@$this->GetIDForIdent('Radio')),
                    'GroupVolume'   => GetValueInteger(@$this->GetIDForIdent('GroupVolume')),
                    'Sleeptimer'    => $sleeptimer,
                    'CoverURL'      => $coverURL,
                    'ContentStream' => $contentStram,
                    'Artist'        => $artist,
                    'Album'         => $album,
                    'TrackDuration' => $trackDuration,
                    'Position'      => $position,
                    'Title'         => $title,
                    'Track'         => $track,
                    'Details'       => $details
                ];
                 return json_encode($result);
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
            case 'getProperties':
                $result = [
                    'instanceID'    => $this->InstanceID,
                    'IPAddress'     => gethostbyname($this->ReadPropertyString('IPAddress')),
                    'RINCON'        => $this->ReadPropertyString('RINCON'),
                    'TimeOut'       => $this->ReadPropertyInteger('TimeOut')
                ];
                 return json_encode($result);
                break;
            default:
                throw new Exception(sprintf($this->Translate('unknown type %s in ReceiveData'), $input['type']));
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
        $groupMembers = $this->ReadAttributeString('GroupMembers');
        if ($groupMembers == 0) {
            $this->SendDebug(__FUNCTION__, 'no group members', 0);
            $name_array[] = 'none';
        } else {
            $data = json_encode([
                'DataID'              => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'                => 'getName',
                'targetInstance'      => array_map('intval', explode(',', $groupMembers))
            ]);
            $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
            $responseJson = $this->SendDataToParent($data);
            $this->SendDebug(__FUNCTION__ . '->received from parent', $responseJson, 0);
            $nameList = json_decode($responseJson, true);
            foreach ($nameList as $name) {
                $name_array[] = json_decode($name, true)['name'];
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

    public function BecomeCoordinator()
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called', '', 0);
        if ($this->ReadAttributeBoolean('Coordinator')) {
            $this->SendDebug(__FUNCTION__, 'already new Coordinator', 0);
            return;
        }

        $data = json_encode([
            'DataID'              => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
            'type'                => 'callFunction',
            'targetInstance'      => GetValue($this->GetIDForIdent('MemberOfGroup')),
            'function'            => 'DelegateGroupCoordinationTo',
            'newGroupCoordinator' => $this->InstanceID,
            'rejoinGroup'         => true
        ]);
        $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
        $this->SendDataToParent($data);
    }

    public function ChangeGroupVolume(int $increment)
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$increment=%d', $increment), 0);

        if (!@$this->ReadAttributeBoolean('Coordinator')) {
            die($this->Translate('This function is only allowed for Coordinators'));
        }

        $groupMembers = $this->ReadAttributeString('GroupMembers');
        $groupMembersArray = [];
        if ($groupMembers) {
            $groupMembersArray = array_map('intval', explode(',', $groupMembers));
        }
        $this->ChangeVolume($increment);

        foreach ($groupMembersArray as $key => $ID) {
            $data = json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'callFunction',
                'targetInstance' => $ID,
                'function'       => 'ChangeVolume',
                'volume'         => (int) $increment
            ]);

            $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);

            try {
                $this->SendDataToParent($data);
            } catch (Exception $e) {
                $this->SendDebug(__FUNCTION__ . '->Exception caught', $e->getMessage(), 0);
            }
        }

        $GroupVolume = GetValueInteger($this->GetIDForIdent('Volume'));

        if ($groupMembersArray) {
            $data = json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'getVariable',
                'targetInstance' => $groupMembersArray,
                'variableIdent'  => 'Volume',
                'variableType'   => 'int'
            ]);

            $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
            $parentResponseJSON = $this->SendDataToParent($data);
            $this->SendDebug(__FUNCTION__ . '->received from parent', $parentResponseJSON, 0);
            $memberVolumeList = json_decode($parentResponseJSON, true);
            foreach ($memberVolumeList as $memberVolume) {
                $GroupVolume += json_decode($memberVolume, true)['variableValue'];
            }
        }

        SetValueInteger($this->GetIDForIdent('GroupVolume'), intval(round($GroupVolume / (count($groupMembersArray) + 1))));
    }

    public function ChangeVolume(int $increment)
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$increment=%d', $increment), 0);
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
        $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$newGroupCoordinator=%d $rejoinGroup=%s', $newGroupCoordinator, $rejoinGroup ? 'true' : 'false'), 0);

        // do nothing if instance is the same as $newGroupCoordinator
        if ($this->InstanceID === $newGroupCoordinator) {
            $this->SendDebug(__FUNCTION__, 'already new Coordinator', 0);
            return;
        }

        // $newGroupCoordinator is not part of group
        $data = json_encode([
            'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
            'type'           => 'getVariable',
            'targetInstance' => $newGroupCoordinator,
            'variableIdent'  => 'MemberOfGroup',
            'variableType'   => 'int'
        ]);

        $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
        $parentResponseJSON = $this->SendDataToParent($data);
        $this->SendDebug(__FUNCTION__ . '->received from parent', $parentResponseJSON, 0);
        // check if returned vaiable value is this Instance ID
        if (json_decode(json_decode($parentResponseJSON, true)[0], true)['variableValue'] !== $this->InstanceID) {
            throw new Exception(sprintf($this->translate('%s is not a member of this group'), $newGroupCoordinator));
        }

        // execute sonos change
        $sonos = $this->getSonosAccess();

        // Is executed on own Instance, but with RINCON of new Coordinator
        $RINCON = $this->getInstanceRINCON($newGroupCoordinator);
        $this->SendDebug(__FUNCTION__ . '->sonos', sprintf('DelegateGroupCoordinationTo(%s, %s)', $RINCON, $rejoinGroup ? 'true' : 'false'), 0);
        $sonos->DelegateGroupCoordinationTo($RINCON, $rejoinGroup);

        // get old membersOf and remove involved instances
        $currentMembers = explode(',', $this->ReadAttributeString('GroupMembers'));
        $currentMembers = array_filter($currentMembers, function ($v)
        {
            return $v != '';
        });
        $currentMembers = array_filter($currentMembers, function ($v)
        {
            return $v != $this->InstanceID;
        });

        $newMembers = [];

        // update memberOf in all members, and clear in new coordinator
        foreach ($currentMembers as $key => $ID) {
            if ($ID != $newGroupCoordinator) {
                $data = json_encode([
                    'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                    'type'           => 'setVariable',
                    'targetInstance' => (int) $ID,
                    'variableType'   => 'int',
                    'variableIdent'  => 'MemberOfGroup',
                    'variableValue'  => (int) $newGroupCoordinator
                ]);
                $newMembers[] = $ID;
            } else {
                $data = json_encode([
                    'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                    'type'           => 'setVariable',
                    'targetInstance' => (int) $ID,
                    'variableType'   => 'int',
                    'variableIdent'  => 'MemberOfGroup',
                    'variableValue'  => 0
                ]);
            }
            $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
            $this->SendDataToParent($data);
        }

        // update GroupMembers in old and new coordinator
        if ($rejoinGroup) {
            $newMembers[] = $this->InstanceID;
        }

        asort($newMembers);
        $data = json_encode([
            'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
            'type'           => 'setAttribute',
            'targetInstance' => $newGroupCoordinator,
            'attributeType'  => 'string',
            'attributeName'  => 'GroupMembers',
            'attributeValue' => implode(',', $newMembers)
        ]);
        $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
        $this->SendDataToParent($data);
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

        $data = json_encode([
            'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
            'type'           => 'setAttribute',
            'targetInstance' => $newGroupCoordinator,
            'attributeType'  => 'bool',
            'attributeName'  => 'Coordinator',
            'attributeValue' => true
        ]);
        $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
        $this->SendDataToParent($data);

        // update SONOS.Groups, achtung: $rejoinGroup
        if ($rejoinGroup) {
            @IPS_SetVariableProfileAssociation('SONOS.Groups', $this->InstanceID, '', '', -1);
        } else {
            @IPS_SetVariableProfileAssociation('SONOS.Groups', $this->InstanceID, IPS_GetName($this->InstanceID), '', -1);
        }

        $data = json_encode([
            'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
            'type'           => 'becomeNewGroupCoordinator',
            'targetInstance' => $newGroupCoordinator
        ]);
        $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
        $this->SendDataToParent($data);

        if ($rejoinGroup) {
            $hidden = true;
        } else {
            $hidden = false;
        }

        @IPS_SetHidden($this->GetIDForIdent('GroupVolume'), true);
        @IPS_SetHidden($this->GetIDForIdent('MemberOfGroup'), false);
        if (!$this->ReadPropertyBoolean('DisableHiding')) {
            @IPS_SetHidden($this->GetIDForIdent('nowPlaying'), $hidden);
            @IPS_SetHidden($this->GetIDForIdent('Radio'), $hidden);
            @IPS_SetHidden($this->GetIDForIdent('Playlist'), $hidden);
            @IPS_SetHidden($this->GetIDForIdent('PlayMode'), $hidden);
            @IPS_SetHidden($this->GetIDForIdent('Crossfade'), $hidden);
            @IPS_SetHidden($this->GetIDForIdent('Status'), $hidden);
            @IPS_SetHidden($this->GetIDForIdent('Sleeptimer'), $hidden);
            @IPS_SetHidden($this->GetIDForIdent('Details'), $hidden);
        }
    } // END DelegateGroupCoordinationTo

    public function DeleteSleepTimer()
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called', '', 0);
        $targetInstance = $this->findTarget();

        if ($targetInstance === $this->InstanceID) {
            $sonos = $this->getSonosAccess();

            $this->SendDebug(__FUNCTION__ . '->sonos', 'SetSleeptimer(0, 0, 0)', 0);
            $sonos->SetSleeptimer(0, 0, 0);
        } else {
            $data = json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'callFunction',
                'targetInstance' => $targetInstance,
                'function'       => 'DeleteSleepTimer'
            ]);
            $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
            $this->SendDataToParent($data);
        }
    }  // END DeleteSleepTimer

    public function IsCoordinator(): bool
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called', '', 0);
        return $this->ReadAttributeBoolean('Coordinator');
    }

    public function Next()
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called', '', 0);
        $targetInstance = $this->findTarget();

        if ($targetInstance === $this->InstanceID) {
            $sonos = $this->getSonosAccess();

            $this->SendDebug(__FUNCTION__ . '->sonos', 'Next()', 0);
            $sonos->Next();
        } else {
            $data = json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'callFunction',
                'targetInstance' => $targetInstance,
                'function'       => 'Next'
            ]);
            $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
            $this->SendDataToParent($data);
        }
    } // END Next

    public function GetMembers(): string
    {
        $groupMembers = $this->ReadAttributeString('GroupMembers');

        if ($groupMembers == 0) {
            return '[]';
        } else {
            return json_encode(array_map('intval', explode(',', $groupMembers)));
        }
    }

    public function Pause()
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called', '', 0);
        $this->PauseInternal(true);
    }   // END Pause

    public function Play()
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called', '', 0);
        $targetInstance = $this->findTarget();

        if ($targetInstance === $this->InstanceID) {
            $sonos = $this->getSonosAccess();

            SetValue($this->GetIDForIdent('Status'), 1);
            $this->SendDebug(__FUNCTION__ . '->sonos', 'Play()', 0);
            $sonos->Play();
        } else {
            $data = json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'callFunction',
                'targetInstance' => $targetInstance,
                'function'       => 'Play'
            ]);
            $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
            $this->SendDataToParent($data);
        }
    }

    public function PlayFiles(string $files, string $volumeChange)
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$files=%s $volumeChange=%s', $files, $volumeChange), 0);
        $filesArray = json_decode($files, true);
        foreach ($filesArray as $key => $file) {
            // only files on SMB share or http server can be used
            if ($file == '') {
                throw new Exception($this->Translate('No file handed over.'));
            } elseif (preg_match('/^\/\/[\w,.,-]*\/\S*/', $file) != 1 && preg_match('/^https{0,1}:\/\/[\w,.,\-,:]*\/\S*/', $file) != 1) {
                throw new Exception(sprintf($this->Translate('File (%s) has to be located on a Samba share (e.g. //ipsymcon.fritz.box/tts/text.mp3) or a HTTP server (e.g. http://ipsymcon.fritz.box/tts/text.mp3)'), $file));
            }
        }

        $sonos = $this->getSonosAccess();

        $positionInfo = $sonos->GetPositionInfo();
        $this->SendDebug(__FUNCTION__ . ': current positionInfo', json_encode($positionInfo), 0);
        $mediaInfo = $sonos->GetMediaInfo();
        $this->SendDebug(__FUNCTION__ . ': current mediaInfo', json_encode($mediaInfo), 0);
        $transportInfo = $sonos->GetTransportInfo();
        $this->SendDebug(__FUNCTION__ . ': current transportInfo', (string) $transportInfo, 0);
        $isGroupCoordinator = @$this->ReadAttributeBoolean('Coordinator');
        $this->SendDebug(__FUNCTION__ . ': current $isGroupCoordinator', $isGroupCoordinator ? 'true' : 'false', 0);

        //adjust volume if needed
        if ($volumeChange != '0') {
            // remember old volume settings
            $volumeList = [];
            $volumeList[$this->InstanceID] = GetValueInteger($this->GetIDForIdent('Volume'));
            if ($isGroupCoordinator) {
                foreach (explode(',', $this->ReadAttributeString('GroupMembers')) as $groupMember) {
                    if ($groupMember == 0) {
                        continue;
                    }

                    $data = json_encode([
                        'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                        'type'           => 'getVariable',
                        'targetInstance' => (int) $groupMember,
                        'variableIdent'  => 'Volume',
                        'variableType'   => 'int'
                    ]);

                    $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
                    $parentResponseJSON = $this->SendDataToParent($data);
                    $this->SendDebug(__FUNCTION__ . '->received from parent', $parentResponseJSON, 0);
                    $volumeList[$groupMember] = json_decode(json_decode($parentResponseJSON, true)[0], true)['variableValue']; // remember old setting
                }
            }
            $this->SendDebug(__FUNCTION__ . ': remember volume', json_encode($volumeList), 0);

            // pause if playing or remove from group
            if (!$isGroupCoordinator) {
                $this->SendDebug(__FUNCTION__ . '->sonos', 'SetAVTransportURI(\'\')', 0);
                $sonos->SetAVTransportURI(''); // Set itself as source, so it is removed from Group
            } elseif ($transportInfo == 1) {
                try {
                    $this->SendDebug(__FUNCTION__ . '->sonos', 'Pause()', 0);
                    $sonos->Pause();
                } catch (Exception $e) {
                    if ($e->getMessage() != 'Error during Soap Call: UPnPError s:Client 701 (ERROR_AV_UPNP_AVT_INVALID_TRANSITION)') {
                        throw $e;
                    } else {
                        $this->SendDebug(__FUNCTION__ . ': caught exception', $e->getMessage(), 0);
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
                $data = json_encode([
                    'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                    'type'           => 'callFunction',
                    'targetInstance' => $ID,
                    'function'       => $function,
                    'volume'         => (int) $volumeChange
                ]);
                $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
                $this->SendDataToParent($data);
            }
        }

        $this->SendDebug(__FUNCTION__, 'start playing files', 0);
        foreach ($filesArray as $key => $file) {
            if (preg_match('/^\/\/[\w,.,-]*\/\S*/', $file) == 1) {
                $uri = 'x-file-cifs:' . $file;
            } elseif (preg_match('/^https{0,1}:\/\/[\w,.,\-,:]*\/\S*/', $file) == 1) {
                $uri = $file;
            }

            $this->SendDebug(__FUNCTION__ . '->sonos', sprintf('SetAVTransportURI(%s)', $uri), 0);
            $sonos->SetAVTransportURI($uri);
            $this->SendDebug(__FUNCTION__ . '->sonos', 'SetPlayMode(0)', 0);
            $sonos->SetPlayMode(0);
            $this->SendDebug(__FUNCTION__ . '->sonos', 'Play()', 0);
            $sonos->Play();
            IPS_Sleep(500);
            $fileTransportInfo = $sonos->GetTransportInfo();
            while ($fileTransportInfo == 1 || $fileTransportInfo == 5) {
                IPS_Sleep(200);
                $fileTransportInfo = $sonos->GetTransportInfo();
            }
        }

        // reset to what was playing before
        $this->SendDebug(__FUNCTION__, 'reset old settings', 0);
        $this->SendDebug(__FUNCTION__ . '->sonos', sprintf('SetAVTransportURI(%s,%s)', $mediaInfo['CurrentURI'], $mediaInfo['CurrentURIMetaData']), 0);
        $sonos->SetAVTransportURI($mediaInfo['CurrentURI'], $mediaInfo['CurrentURIMetaData']);
        if ($positionInfo['TrackDuration'] != '0:00:00' && $positionInfo['Track'] > 1) {
            try {
                $this->SendDebug(__FUNCTION__ . '->sonos', sprintf('Seek(\'TRACK_NR\',%s)', $positionInfo['Track']), 0);
                $sonos->Seek('TRACK_NR', $positionInfo['Track']);
            } catch (Exception $e) {
            }
        }
        if ($positionInfo['TrackDuration'] != '0:00:00' && $positionInfo['RelTime'] != 'NOT_IMPLEMENTED') {
            try {
                $this->SendDebug(__FUNCTION__ . '->sonos', sprintf('Seek(\'REL_TIME\',%s)', $positionInfo['RelTime']), 0);
                $sonos->Seek('REL_TIME', $positionInfo['RelTime']);
            } catch (Exception $e) {
            }
        }

        if ($volumeChange != '0') {
            // set back volume
            foreach ($volumeList as $ID => $volume) {
                $data = json_encode([
                    'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                    'type'           => 'callFunction',
                    'targetInstance' => $ID,
                    'function'       => 'SetVolume',
                    'volume'         => $volume
                ]);
                $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
                $this->SendDataToParent($data);
            }
        }

        // If it was playing before, play again
        if ($transportInfo == 1) {
            $this->SendDebug(__FUNCTION__ . '->sonos', 'Play()', 0);
            $sonos->Play();
            $this->SendDebug(__FUNCTION__, 'waiting until it is really playing...', 0);
            for ($i = 0; $i < 10; $i++) {
                $transportInfo = $sonos->GetTransportInfo();
                if ($transportInfo !== 1) {
                    IPS_Sleep(200);
                } else {
                    $this->SendDebug(__FUNCTION__, 'done, now it is playing.', 0);
                    break;
                }
            }
        }
    } // END PlayFiles

    public function PlayFilesGrouping(string $instances, string $files, string $volumeChange)
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$instances=%s $files=%s $volumeChange=%s', $instances, $files, $volumeChange), 0);
        // check if files are OK
        $filesArray = json_decode($files, true);
        foreach ($filesArray as $key => $file) {
            // only files on SMB share or http server can be used
            if ($file == '') {
                throw new Exception($this->Translate('No file handed over.'));
            } elseif (preg_match('/^\/\/[\w,.,-]*\/\S*/', $file) != 1 && preg_match('/^https{0,1}:\/\/[\w,.,\-,:]*\/\S*/', $file) != 1) {
                throw new Exception(sprintf($this->Translate('File (%s) has to be located on a Samba share (e.g. //ipsymcon.fritz.box/tts/text.mp3) or a HTTP server (e.g. http://ipsymcon.fritz.box/tts/text.mp3)'), $file));
            }
        }

        $sonos = $this->getSonosAccess();

        $instancesArray = json_decode($instances, true);
        $involvedInstances = array_keys($instancesArray);
        $involvedInstances[] = $this->InstanceID;

        $data = json_encode([
            'DataID'            => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
            'type'              => 'prepareAllPlayGrouping',
            'targetInstance'    => null,
            'involvedInstances' => $involvedInstances
        ]);
        $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
        $this->SendDataToParent($data);

        $transportURI = 'x-rincon:' . $this->ReadPropertyString('RINCON');

        foreach ($instancesArray as $instanceID => $settings) {
            if (isset($settings['volume'])) {
                $volume = $settings['volume'];
            } else {
                $volume = '0';
            }

            $data = json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'preparePlayGrouping',
                'targetInstance' => $instanceID,
                'volumeChange'   => strval($volume),
                'transportURI'   => $transportURI
            ]);
            $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
            $this->SendDataToParent($data);
        }

        //also set volume for this instance
        if ($this->ReadAttributeBoolean('OutputFixed') == false) {
            if ($volumeChange != 0) {
                // volume request absolte or relative?
                if ($volumeChange[0] == '+' || $volumeChange[0] == '-') {
                    $newVolume = ($sonos->GetVolume() + $volumeChange);
                } else {
                    $newVolume = $volumeChange;
                }

                if ($newVolume > 100) {
                    $newVolume = 100;
                } elseif ($newVolume < 0) {
                    $newVolume = 0;
                }

                $this->SendDebug(__FUNCTION__ . '->sonos', sprintf('SetVolume(%d)', $newVolume), 0);
                $sonos->SetVolume($newVolume);
            }
        }
        // also unmute this instance if muted
        if ($sonos->GetMute()) {
            $this->SendDebug(__FUNCTION__ . '->sonos', 'SetMute(false)', 0);
            $sonos->SetMute(false);
        }

        // play files
        foreach ($filesArray as $key => $file) {
            if (preg_match('/^\/\/[\w,.,-]*\/\S*/', $file) == 1) {
                $uri = 'x-file-cifs:' . $file;
            } elseif (preg_match('/^https{0,1}:\/\/[\w,.,\-,:]*\/\S*/', $file) == 1) {
                $uri = $file;
            }

            $this->SendDebug(__FUNCTION__ . '->sonos', sprintf('SetAVTransportURI(%s)', $uri), 0);
            $sonos->SetAVTransportURI($uri);
            $this->SendDebug(__FUNCTION__ . '->sonos', 'SetPlayMode(0)', 0);
            $sonos->SetPlayMode(0);
            $this->SendDebug(__FUNCTION__ . '->sonos', 'Play()', 0);
            $sonos->Play();
            IPS_Sleep(500);
            $fileTransportInfo = $sonos->GetTransportInfo();
            while ($fileTransportInfo == 1 || $fileTransportInfo == 5) {
                IPS_Sleep(200);
                $fileTransportInfo = $sonos->GetTransportInfo();
            }
        }

        // prepare reset all players, including myself, e.g. remove from group
        $data = json_encode([
            'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
            'type'           => 'preResetPlayGrouping',
            'targetInstance' => $involvedInstances
        ]);
        $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
        $this->SendDataToParent($data);

        // reset all players, including myself
        $data = json_encode([
            'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
            'type'           => 'resetPlayGrouping',
            'targetInstance' => null
        ]);
        $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
        $this->SendDataToParent($data);
    } // END PlayFilesGrouping

    public function Previous()
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called', '', 0);
        $targetInstance = $this->findTarget();

        if ($targetInstance === $this->InstanceID) {
            $sonos = $this->getSonosAccess();

            $this->SendDebug(__FUNCTION__ . '->sonos', 'Previous()', 0);
            $sonos->Previous();
        } else {
            $data = json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'callFunction',
                'targetInstance' => $targetInstance,
                'function'       => 'Previous'
            ]);
            $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
            $this->SendDataToParent($data);
        }
    } // END Previous

    public function RampToVolume(string $rampType, int $volume)
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$rampType=%s $volume=%d', $rampType, $volume), 0);
        if ($this->ReadAttributeBoolean('OutputFixed') == false) {
            $sonos = $this->getSonosAccess();

            SetValue($this->GetIDForIdent('Volume'), $volume);
            $this->SendDebug(__FUNCTION__ . '->sonos', sprintf('RampToVolume(%s, %d)', $rampType, $volume), 0);
            $sonos->RampToVolume($rampType, $volume);
        }
    } // END RampToVolume

    public function SetAnalogInput(int $input_instance)
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$input_instance=%d', $input_instance), 0);
        $sonos = $this->getSonosAccess();

        if (@GetValue($this->GetIDForIdent('MemberOfGroup'))) {
            $this->SetGroup(0);
        }

        $uri = 'x-rincon-stream:' . $this->getInstanceRINCON($input_instance);
        $this->SendDebug(__FUNCTION__ . '->sonos', sprintf('SetAVTransportURI(%s)', $uri), 0);
        $sonos->SetAVTransportURI($uri);
    }    // END SetAnalogInput

    public function SetBalance(int $balance)
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$balance=%d', $balance), 0);
        $sonos = $this->getSonosAccess();

        $leftVolume = 100;
        $rightVolume = 100;
        if ($balance < 0) {
            $rightVolume = 100 + $balance;
        } else {
            $leftVolume = 100 - $balance;
        }

        $this->SendDebug(__FUNCTION__ . '->sonos', sprintf('SetVolume(%d, \'LF\')', $leftVolume), 0);
        $sonos->SetVolume($leftVolume, 'LF');
        $this->SendDebug(__FUNCTION__ . '->sonos', sprintf('SetVolume(%d, \'RF\')', $rightVolume), 0);
        $sonos->SetVolume($rightVolume, 'RF');
        if (!$this->ReadPropertyBoolean('BalanceControl')) {
            SetValue($this->GetIDForIdent('Balance'), $balance);
        }
    } // END SetBalance

    public function SetBass(int $bass)
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$bass=%d', $bass), 0);
        $sonos = $this->getSonosAccess();

        $this->SendDebug(__FUNCTION__ . '->sonos', sprintf('SetBass(%d)', $bass), 0);
        $sonos->SetBass($bass);
        if (!$this->ReadPropertyBoolean('BassControl')) {
            SetValue($this->GetIDForIdent('Bass'), $bass);
        }
    }    // END SetBass

    public function SetCrossfade(bool $crossfade)
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$crossfade=%s', $crossfade ? 'true' : 'false'), 0);
        $targetInstance = $this->findTarget();

        if ($targetInstance === $this->InstanceID) {
            $sonos = $this->getSonosAccess();

            $this->SendDebug(__FUNCTION__ . '->sonos', sprintf('SetCrossfade(%s)', $crossfade ? 'true' : 'false'), 0);
            $sonos->SetCrossfade($crossfade);
            if ($this->ReadPropertyBoolean('PlayModeControl')) {
                SetValue($this->GetIDForIdent('Crossfade'), $crossfade);
            }
        } else {
            $data = json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'callFunction',
                'targetInstance' => $targetInstance,
                'function'       => 'SetCrossfade',
                'crossfade'      => $crossfade
            ]);
            $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
            $this->SendDataToParent($data);
        }
    }   // END SetCrossfade

    public function SetDefaultGroupVolume()
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called', '', 0);
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
                $data = json_encode([
                    'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                    'type'           => 'callFunction',
                    'targetInstance' => $ID,
                    'function'       => 'SetDefaultVolume'
                ]);
                $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
                $this->SendDataToParent($data);
            } catch (Exception $e) {
                $this->SendDebug(__FUNCTION__ . '->Exception caught', $e->getMessage(), 0);
            }
        }

        $GroupVolume = 0;
        $data = json_encode([
            'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
            'type'           => 'getVariable',
            'targetInstance' => $groupMembersArray,
            'variableIdent'  => 'Volume',
            'variableType'   => 'int'
        ]);

        $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
        $parentResponseJSON = $this->SendDataToParent($data);
        $this->SendDebug(__FUNCTION__ . '->received from parent', $parentResponseJSON, 0);
        $memberVolumeList = json_decode($parentResponseJSON, true);
        foreach ($memberVolumeList as $memberVolume) {
            $GroupVolume += json_decode($memberVolume, true)['variableValue'];
        }

        SetValueInteger($this->GetIDForIdent('GroupVolume'), intval(round($GroupVolume / count($groupMembersArray))));
    }    // END  SetDefaultGroupVolume

    public function SetDefaultVolume()
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called', '', 0);
        try {
            $this->SetVolume($this->ReadPropertyInteger('DefaultVolume'));
        } catch (Exception $e) {
            throw $e;
        }
    } // SetDefaultVolume

    public function SetDialogLevel(bool $dialogLevel)
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$dialogLevel=%s', $dialogLevel ? 'true' : 'false'), 0);
        $sonos = $this->getSonosAccess();

        try {
            $this->SendDebug(__FUNCTION__ . '->sonos', sprintf('SetDialogLevel(%s)', $dialogLevel ? 'true' : 'false'), 0);
            $sonos->SetDialogLevel($dialogLevel);
        } catch (Exception $e) {
            if ($e->getMessage() == 'Error during Soap Call: UPnPError s:Client 402 (UNKNOWN)') {
                throw new Exception($this->translate('This device does not support DialogLevel'));
            } else {
                throw $e;
            }
        }
        if ($this->ReadPropertyBoolean('NightModeControl')) {  // same switch as Night Mode
            SetValue($this->GetIDForIdent('DialogLevel'), $dialogLevel);
        }
    }   // END SetDialogLevel

    public function SetGroup(int $groupCoordinator)
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$groupCoordinator=%d', $groupCoordinator), 0);
        // Instance has Members, do nothing
        if (@$this->ReadAttributeString('GroupMembers')) {
            $this->SendDebug(__FUNCTION__ . ': return', 'Instance has Members, do nothing', 0);
            return;
        }
        // Do not try to assign to itself
        if ($this->InstanceID === $groupCoordinator) {
            $this->SendDebug(__FUNCTION__ . ': info', 'Instance is same as requested Coordinator, using 0', 0);
            $groupCoordinator = 0;
        }

        $startGroupCoordinator = GetValue($this->GetIDForIdent('MemberOfGroup'));
        $this->SendDebug(__FUNCTION__ . ': old coordinator', (string) $startGroupCoordinator, 0);

        $sonos = $this->getSonosAccess();

        // cleanup old group
        if ($startGroupCoordinator) {
            $data = json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'removeMember',
                'targetInstance' => $startGroupCoordinator,
                'instanceID'     => $this->InstanceID
            ]);
            $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
            $this->SendDataToParent($data);
        }

        // adjust new Group
        $currentMembers = [];
        if ($groupCoordinator) {
            $data = json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'addMember',
                'targetInstance' => $groupCoordinator,
                'instanceID'     => $this->InstanceID
            ]);
            $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
            $this->SendDataToParent($data);

            // get RINCON from Coordinator
            $uri = 'x-rincon:' . $this->getInstanceRINCON($groupCoordinator);
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

        if (!$this->ReadPropertyBoolean('DisableHiding')) {
            @IPS_SetHidden($this->GetIDForIdent('nowPlaying'), $hidden);
            @IPS_SetHidden($this->GetIDForIdent('Radio'), $hidden);
            @IPS_SetHidden($this->GetIDForIdent('Playlist'), $hidden);
            @IPS_SetHidden($this->GetIDForIdent('PlayMode'), $hidden);
            @IPS_SetHidden($this->GetIDForIdent('Crossfade'), $hidden);
            @IPS_SetHidden($this->GetIDForIdent('Status'), $hidden);
            @IPS_SetHidden($this->GetIDForIdent('Sleeptimer'), $hidden);
            @IPS_SetHidden($this->GetIDForIdent('Details'), $hidden);
        }
        // always hide GroupVolume, unhide executed on GroupCoordinator a few lines above
        @IPS_SetHidden($this->GetIDForIdent('GroupVolume'), true);
        @IPS_SetHidden($this->GetIDForIdent('MemberOfGroup'), false);

        $this->SendDebug(__FUNCTION__ . '->sonos', sprintf('SetAVTransportURI(%s)', $uri), 0);
        $sonos->SetAVTransportURI($uri);
    } // END SetGroup

    public function SetGroupVolume(int $volume)
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$volume=%d', $volume), 0);
        if (!@$this->ReadAttributeBoolean('Coordinator')) {
            die($this->Translate('This function is only allowed for Coordinators'));
        }

        $this->ChangeGroupVolume($volume - GetValue($this->GetIDForIdent('GroupVolume')));
    }    // END SetGroupVolume

    public function SetHdmiInput(int $input_instance)
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$input_instance=%d', $input_instance), 0);
        // seems to be the same as S/PDIF
        $this->SetSpdifInput($input_instance);
    }    // END SetHdmiInput

    public function SetLoudness(bool $loudness)
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$loudness=%s', $loudness ? 'true' : 'false'), 0);
        $sonos = $this->getSonosAccess();

        $this->SendDebug(__FUNCTION__ . '->sonos', sprintf('SetLoudness(%s)', $loudness ? 'true' : 'false'), 0);
        $sonos->SetLoudness($loudness);
        if ($this->ReadPropertyBoolean('LoudnessControl')) {
            SetValue($this->GetIDForIdent('Loudness'), $loudness);
        }
    } //  END SetLoudness

    public function SetMute(bool $mute)
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$mute=%s', $mute ? 'true' : 'false'), 0);
        $sonos = $this->getSonosAccess();

        $this->SendDebug(__FUNCTION__ . '->sonos', sprintf('SetMute(%s)', $mute ? 'true' : 'false'), 0);
        $sonos->SetMute($mute);
        if ($this->ReadPropertyBoolean('MuteControl')) {
            SetValue($this->GetIDForIdent('Mute'), $mute);
        }
    }   // END SetMute

    public function SetMuteGroup(bool $mute)
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$mute=%s', $mute ? 'true' : 'false'), 0);

        if (!@$this->ReadAttributeBoolean('Coordinator')) {
            die($this->Translate('This function is only allowed for Coordinators'));
        }

        $groupMembers = $this->ReadAttributeString('GroupMembers');
        $groupMembersArray = [];
        if ($groupMembers) {
            $groupMembersArray = array_map('intval', explode(',', $groupMembers));
        }
        $this->SetMute($mute);

        foreach ($groupMembersArray as $key => $ID) {
            $data = json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'callFunction',
                'targetInstance' => $ID,
                'function'       => 'SetMute',
                'mute'           => (bool) $mute
            ]);

            $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);

            try {
                $this->SendDataToParent($data);
            } catch (Exception $e) {
                $this->SendDebug(__FUNCTION__ . '->Exception caught', $e->getMessage(), 0);
            }
        }
    }   // END SetMute

    public function SetNightMode(bool $nightMode)
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$nightMode=%s', $nightMode ? 'true' : 'false'), 0);
        $sonos = $this->getSonosAccess();

        try {
            $this->SendDebug(__FUNCTION__ . '->sonos', sprintf('SetNightMode(%s)', $nightMode ? 'true' : 'false'), 0);
            $sonos->SetNightMode($nightMode);
        } catch (Exception $e) {
            if ($e->getMessage() == 'Error during Soap Call: UPnPError s:Client 402 (UNKNOWN)') {
                throw new Exception($this->translate('This device does not support NightMode'));
            } else {
                throw $e;
            }
        }

        if ($this->ReadPropertyBoolean('NightModeControl')) {
            SetValue($this->GetIDForIdent('NightMode'), $nightMode);
        }
    }   // END SetNightMode

    public function SetPlaylist(string $name)
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$name=%s', $name), 0);
        $sonos = $this->getSonosAccess();

        if (@GetValue($this->GetIDForIdent('MemberOfGroup'))) {
            $this->SetGroup(0);
        }

        $uri = '';
        $meta = '';

        $this->SendDebug(__FUNCTION__ . '->sonos', 'BrowseContentDirectory(\'SQ:\', \'BrowseDirectChildren\', 999)', 0);
        foreach ((new SimpleXMLElement($sonos->BrowseContentDirectory('SQ:', 'BrowseDirectChildren', 999)['Result']))->container as $container) {
            $this->SendDebug(__FUNCTION__ . ': Found Playlist', (string) $container->xpath('dc:title')[0], 0);
            if ($container->xpath('dc:title')[0] == $name) {
                $uri = (string) $container->res;
                break;
            }
        }

        if ($uri === '') {
            $this->SendDebug(__FUNCTION__ . '->sonos', 'BrowseContentDirectory(\'FV:2\', \'BrowseDirectChildren\', 999)', 0);
            foreach ((new SimpleXMLElement($sonos->BrowseContentDirectory('FV:2', 'BrowseDirectChildren', 999)['Result']))->item as $item) {
                $this->SendDebug(__FUNCTION__ . ': Found Playlist', (string) $item->xpath('dc:title')[0], 0);
                if (preg_replace($this->getPlaylistReplacementFrom(), $this->getPlaylistReplacementTo(), $item->xpath('dc:title')[0]) == $name) {
                    $uri = (string) $item->res;
                    $meta = (string) $item->xpath('r:resMD')[0];
                    break;
                }
            }
        }

        if ($uri === '') {
            $this->SendDebug(__FUNCTION__ . '->sonos', 'BrowseContentDirectory(\'A:PLAYLISTS\', \'BrowseDirectChildren\', 999)', 0);
            foreach ((new SimpleXMLElement($sonos->BrowseContentDirectory('A:PLAYLISTS', 'BrowseDirectChildren', 999)['Result']))->container as $container) {
                $this->SendDebug(__FUNCTION__ . ': Found Playlist', (string) $container->xpath('dc:title')[0], 0);
                if (preg_replace($this->getPlaylistReplacementFrom(), $this->getPlaylistReplacementTo(), $container->xpath('dc:title')[0]) == $name) {
                    $uri = (string) $container->res;
                    break;
                }
            }
        }

        if ($uri === '') {
            throw new Exception('Playlist \'' . $name . '\' not found');
        }

        $this->SendDebug(__FUNCTION__ . '->sonos', 'ClearQueue()', 0);
        $sonos->ClearQueue();
        $this->SendDebug(__FUNCTION__ . '->sonos', sprintf('AddToQueue(%s, %s)', $uri, $meta), 0);
        $sonos->AddToQueue($uri, $meta);
        $uri = 'x-rincon-queue:' . $this->ReadPropertyString('RINCON') . '#0';
        $this->SendDebug(__FUNCTION__ . '->sonos', sprintf('SetAVTransportURI(%s)', $uri), 0);
        $sonos->SetAVTransportURI($uri);
    }    // END SetPlaylist

    public function SetPlayMode(int $playMode)
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$playMode=%d', $playMode), 0);
        $targetInstance = $this->findTarget();

        if ($targetInstance === $this->InstanceID) {
            $sonos = $this->getSonosAccess();

            $this->SendDebug(__FUNCTION__ . '->sonos', sprintf('SetPlayMode(%d)', $playMode), 0);
            $sonos->SetPlayMode($playMode);
            if ($this->ReadPropertyBoolean('PlayModeControl')) {
                SetValue($this->GetIDForIdent('PlayMode'), $playMode);
            }
        } else {
            $data = json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'callFunction',
                'targetInstance' => $targetInstance,
                'function'       => 'SetPlayMode',
                'playmode'       => $playMode
            ]);
            $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
            $this->SendDataToParent($data);
        }
    } // END SetPlayMode

    public function SetRadio(string $radio)
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$radio=%s', $radio), 0);
        $sonos = $this->getSonosAccess();

        if (@GetValue($this->GetIDForIdent('MemberOfGroup'))) {
            $this->SetGroup(0);
        }

        $uri = '';

        // try to find Radio Station URL
        try {
            $this->SendDebug(__FUNCTION__ . '->getRadioURL', $radio, 0);
            $uri = $this->getRadioURL($radio);
        } catch (Exception $e) {
            // not found in Splitter instance
            // check in TuneIn Favorites
            $this->SendDebug(__FUNCTION__ . '->sonos', 'BrowseContentDirectory(\'R:0/0\')', 0);
            foreach ((new SimpleXMLElement($sonos->BrowseContentDirectory('R:0/0')['Result']))->item as $item) {
                $this->SendDebug(__FUNCTION__ . ': Found radio', (string) $item->xpath('dc:title')[0], 0);
                if ($item->xpath('dc:title')[0] == $radio) {
                    $uri = (string) $item->res;
                    break;
                }
            }
        }

        if ($uri == '') {
            throw new Exception(sprintf($this->Translate('Radio station "%s" not found'), $radio));
        }

        $this->SendDebug(__FUNCTION__ . '->sonos', sprintf('SetRadio(%s, %s)', $uri, $radio), 0);
        $sonos->SetRadio($uri, $radio);
    } // END SetRadio

    public function SetSleepTimer(int $minutes)
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$minutes=%d', $minutes), 0);
        $targetInstance = $this->findTarget();

        if ($targetInstance === $this->InstanceID) {
            $sonos = $this->getSonosAccess();

            $hours = 0;

            while ($minutes > 59) {
                $hours = $hours + 1;
                $minutes = $minutes - 60;
            }

            $this->SendDebug(__FUNCTION__ . '->sonos', sprintf('SetSleeptimer(%d, %d, 0)', $hours, $minutes), 0);
            $sonos->SetSleeptimer($hours, $minutes, 0);
        } else {
            $data = json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'callFunction',
                'targetInstance' => $targetInstance,
                'function'       => 'SetSleepTimer',
                'sleeptimer'     => $minutes
            ]);
            $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
            $this->SendDataToParent($data);
        }
    } // SetSleepTimer

    public function SetSpdifInput(int $input_instance)
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$input_instance=%d', $input_instance), 0);
        $sonos = $this->getSonosAccess();

        if (@GetValue($this->GetIDForIdent('MemberOfGroup'))) {
            $this->SetGroup(0);
        }

        $uri = 'x-sonos-htastream:' . $this->getInstanceRINCON($input_instance) . ':spdif';
        $this->SendDebug(__FUNCTION__ . '->sonos', sprintf('SetAVTransportURI(%s)', $uri), 0);
        $sonos->SetAVTransportURI($uri);
    } // END SetSpdifInput

    public function SetTransportURI(string $uri)
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$uri=%s', $uri), 0);
        $sonos = $this->getSonosAccess();

        if (@GetValue($this->GetIDForIdent('MemberOfGroup'))) {
            $this->SetGroup(0);
        }

        $this->SendDebug(__FUNCTION__ . '->sonos', sprintf('SetAVTransportURI(%s)', $uri), 0);
        $sonos->SetAVTransportURI($uri);
    } // END SetTransportURI

    public function SetTrack(int $track)
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called', '', 0);
        $targetInstance = $this->findTarget();

        if ($targetInstance === $this->InstanceID) {
            $sonos = $this->getSonosAccess();

            $this->SendDebug(__FUNCTION__ . '->sonos', 'SetTrack(' . $track . ')', 0);
            $sonos->SetTrack($track);
        } else {
            $data = json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'callFunction',
                'targetInstance' => $targetInstance,
                'function'       => 'SetTrack',
                'track'          => $track
            ]);
            $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
            $this->SendDataToParent($data);
        }
    }

    public function SetTreble(int $treble)
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$treble=%d', $treble), 0);
        $sonos = $this->getSonosAccess();

        $this->SendDebug(__FUNCTION__ . '->sonos', sprintf('SetTreble(%d)', $treble), 0);
        $sonos->SetTreble($treble);
        if (!$this->ReadPropertyBoolean('TrebleControl')) {
            SetValue($this->GetIDForIdent('Treble'), $treble);
        }
    } // END SetTreble

    public function SetVolume(int $volume)
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$volume=%d', $volume), 0);
        if ($this->ReadAttributeBoolean('OutputFixed') == false) {
            $sonos = $this->getSonosAccess();

            SetValue($this->GetIDForIdent('Volume'), $volume);
            $this->SendDebug(__FUNCTION__ . '->sonos', sprintf('SetVolume(%d)', $volume), 0);
            $sonos->SetVolume($volume);
        }
    } // END SetVolume

    public function Stop()
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called', '', 0);
        $this->StopInternal(true);
    } //END Stop

    // end of public Functions for End users

    public function updateStatus()
    {
        try {
            $sonos = $this->getSonosAccess(false);
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
        $vidTrack = @$this->GetIDForIdent('Track');
        $vidAlbum = @$this->GetIDForIdent('Album');
        $vidTrackDuration = @$this->GetIDForIdent('TrackDuration');
        $vidPosition = @$this->GetIDForIdent('Position');
        $vidCrossfade = @$this->GetIDForIdent('Crossfade');
        $vidPlaymode = @$this->GetIDForIdent('PlayMode');
        $vidGroupVolume = @$this->GetIDForIdent('GroupVolume');
        $vidBattery = @$this->GetIDForIdent('Battery');
        $vidPowerSource = @$this->GetIDForIdent('PowerSource');

        $AlbumArtHeight = $this->ReadAttributeInteger('AlbumArtHeight');

        try {
            $volume = $sonos->GetVolume();
            SetValueInteger($vidVolume, $volume);
            if ($sonos->GetOutputFixed()) {
                if ($this->ReadAttributeBoolean('OutputFixed') == false) {
                    $this->WriteAttributeBoolean('OutputFixed', true);
                    $this->DisableAction('Volume');
                }
            } else {
                if ($this->ReadAttributeBoolean('OutputFixed') == true) {
                    $this->WriteAttributeBoolean('OutputFixed', false);
                    $this->EnableAction('Volume');
                }
            }

            if ($vidMute) {
                SetValue($vidMute, $sonos->GetMute());
            }

            if ($vidBattery) {
                SetValue($vidBattery, $sonos->GetBatteryLevel());
            }

            if ($vidPowerSource) {
                SetValue($vidPowerSource, $sonos->GetPowerSource());
            }

            if ($vidNightMode) {
                try {
                    SetValue($vidNightMode, $sonos->GetNightMode());
                } catch (Exception $e) {
                    if ($e->getMessage() == 'Error during Soap Call: UPnPError s:Client 402 (UNKNOWN)') {
                        throw new Exception($this->translate('This device does not support NightMode'));
                    } else {
                        throw $e;
                    }
                }
            }
            if ($vidDialogLevel) {
                try {
                    SetValue($vidDialogLevel, $sonos->GetDialogLevel());
                } catch (Exception $e) {
                    if ($e->getMessage() == 'Error during Soap Call: UPnPError s:Client 402 (UNKNOWN)') {
                        throw new Exception($this->translate('This device does not support DialogLevel'));
                    } else {
                        throw $e;
                    }
                }
            }
            if ($vidLoudness) {
                SetValue($vidLoudness, $sonos->GetLoudness());
            }
            if ($vidBass) {
                SetValueInteger($vidBass, $sonos->GetBass());
            }
            if ($vidTreble) {
                SetValueInteger($vidTreble, $sonos->GetTreble());
            }
            if ($vidCrossfade) {
                SetValue($vidCrossfade, $sonos->GetCrossfade());
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
                $data = json_encode([
                    'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                    'type'           => 'getCoordinatorValues',
                    'targetInstance' => $MemberOfGroup
                ]);
                $parentResponseJSON = $this->SendDataToParent($data);
                $parentResponse = json_decode($parentResponseJSON, true);

                if ($parentResponse) {
                    $coordinatorValues = json_decode($parentResponse[0], true);

                    SetValueInteger($vidStatus, $coordinatorValues['Status']);
                    $actuallyPlaying = $coordinatorValues['nowPlaying'];
                    SetValueInteger($vidRadio, $coordinatorValues['Radio']);
                    SetValueInteger($vidGroupVolume, $coordinatorValues['GroupVolume']);
                    if ($vidSleeptimer) {
                        SetValueInteger($vidSleeptimer, $coordinatorValues['Sleeptimer']);
                    }
                    if ($vidCoverURL) {
                        SetValueString($vidCoverURL, $coordinatorValues['CoverURL']);
                    }
                    if ($vidContentStream) {
                        SetValueString($vidContentStream, $coordinatorValues['ContentStream']);
                    }
                    if ($vidArtist) {
                        SetValueString($vidArtist, $coordinatorValues['Artist']);
                    }
                    if ($vidAlbum) {
                        SetValueString($vidAlbum, $coordinatorValues['Album']);
                    }
                    if ($vidTrackDuration) {
                        SetValueString($vidTrackDuration, $coordinatorValues['TrackDuration']);
                    }
                    if ($vidPosition) {
                        SetValueString($vidPosition, $coordinatorValues['Position']);
                    }
                    if ($vidTitle) {
                        SetValueString($vidTitle, $coordinatorValues['Title']);
                    }
                    if ($vidTrack) {
                        SetValueInteger($vidTrack, $coordinatorValues['Track']);
                    }
                    if ($vidDetails) {
                        SetValueString($vidDetails, $coordinatorValues['Details']);
                    }
                } else {
                    $this->SendDebug(__FUNCTION__ . '->GetCoordinatorValues', 'No data returned', 0);
                }
            } else {
                $status = $sonos->GetTransportInfo();
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

                    foreach ($radioStations as $radioStation) {
                        if (htmlspecialchars_decode($radioStation['URL']) == htmlspecialchars_decode($mediaInfo['CurrentURI'])) {
                            $image = $radioStation['imageURL'];

                            $Associations = IPS_GetVariableProfile('SONOS.Radio')['Associations'];
                            foreach ($Associations as $key => $station) {
                                if ($station['Name'] == $radioStation['name']) {
                                    $currentStation = $station['Value'];
                                    break;
                                }
                            }
                            break;
                        }
                    }
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
                if ($vidTrack) {
                    SetValueInteger($vidTrack, @$positionInfo['Track']);
                }
                if ($vidTitle) {
                    if (@$mediaInfo['title']) {
                        SetValueString($vidTitle, @$mediaInfo['title']);
                    } else {
                        SetValueString($vidTitle, @$positionInfo['title']);
                    }
                }

                if ($vidDetails) {
                    $stationID = '';
                    if (isset($positionInfo)) {
                        // SPDIF and analog
                        if (strpos($mediaInfo['title'], 'RINCON_') === 0) {
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
                                    //$serial = substr($this->ReadPropertyString('RINCON'), 7, 12);
                                    //$image = preg_replace('#(.*)<LOGO>(.*?)\</LOGO>(.*)#is', '$2', @file_get_contents('http://opml.radiotime.com/Describe.ashx?c=nowplaying&id=' . $stationID . '&partnerId=IAeIhU42&serial=' . $serial));
                                    $image = 'https://cdn-profiles.tunein.com/' . $stationID . '/images/logod.png?t=1';
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
                        $oldURL = GetValueString($vidCoverURL);
                        $imageContent = 'notSet';
                        if ((isset($image)) && (strlen($image) > 0)) {
                            if ($oldURL != $image) {
                                SetValueString($vidCoverURL, $image);
                                $imageContentResponse = @Sys_GetURLContent($image);
                                if ($imageContentResponse) {
                                    $imageContent = base64_encode($imageContentResponse);
                                }
                            }
                        } elseif (isset($positionInfo['albumArtURI']) && (strlen($positionInfo['albumArtURI']) > 0)) {
                            if ($oldURL != $positionInfo['albumArtURI']) {
                                SetValueString($vidCoverURL, $positionInfo['albumArtURI']);
                                $imageContentResponse = @Sys_GetURLContent($positionInfo['albumArtURI']);
                                if ($imageContentResponse) {
                                    $imageContent = base64_encode($imageContentResponse);
                                }
                            }
                        } else {
                            if ($oldURL != '') {
                                SetValueString($vidCoverURL, '');
                                $imageContent = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='; // transparent picture
                            }
                        }
                        $MediaID = @$this->GetIDForIdent('Cover');
                        if ($MediaID && IPS_MediaExists($MediaID) && $imageContent != 'notSet') {
                            IPS_SetMediaContent($MediaID, $imageContent);
                            IPS_SendMediaEvent($MediaID);
                        }
                    }
                    SetValueString($vidStationID, $stationID);
                }

                // Sleeptimer
                if ($vidSleeptimer) {
                    try {
                        $sleeptimer = $sonos->GetSleeptimer();
                    } catch (Exception $e) {
                        if ($e->getMessage() == 'Error during Soap Call: UPnPError s:Client 800 (UNKNOWN)') {
                            // INVALID_TRANSITION happens e.g. when no resource set
                            $this->SendDebug(__FUNCTION__ . '->GetSleeptimer', (string) $e->getMessage(), 0);
                            $sleeptimer = false;
                        } else {
                            throw $e;
                            $sleeptimer = false;
                        }
                    }

                    if ($sleeptimer) {
                        $SleeptimerArray = explode(':', $sleeptimer);
                        $SleeptimerMinutes = $SleeptimerArray[0] * 60 + $SleeptimerArray[1];
                        if ($SleeptimerArray[2]) {
                            $SleeptimerMinutes = $SleeptimerMinutes + 1;
                        }
                    } else {
                        $SleeptimerMinutes = 0;
                    }

                    SetValueInteger($vidSleeptimer, $SleeptimerMinutes);

                    // Set Group Volume
                    $groupMembers = $this->ReadAttributeString('GroupMembers');
                    $groupMembersArray = [];
                    if ($groupMembers) {
                        $groupMembersArray = array_map('intval', explode(',', $groupMembers));
                    }

                    $GroupVolume = $volume; // add own volume
                    if ($groupMembersArray) {
                        $data = json_encode([
                            'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                            'type'           => 'getVariableNoDebug',
                            'targetInstance' => $groupMembersArray,
                            'variableIdent'  => 'Volume',
                            'variableType'   => 'int'
                        ]);

                        $parentResponseJSON = $this->SendDataToParent($data);
                        $memberVolumeList = json_decode($parentResponseJSON, true);
                        foreach ($memberVolumeList as $memberVolume) {
                            $GroupVolume += json_decode($memberVolume, true)['variableValue'];
                        }
                    }

                    SetValueInteger($vidGroupVolume, intval(round($GroupVolume / (count($groupMembersArray) + 1))));
                }
            }

            $nowPlaying = GetValueString($vidNowPlaying);
            if ($actuallyPlaying != $nowPlaying) {
                SetValueString($vidNowPlaying, $actuallyPlaying);
            }
        } catch (Exception $e) {
            $eMessage = $e->getMessage();
            if ($eMessage == 'Error during Soap Call: Could not connect to host HTTP' || $eMessage == 'Error during Soap Call: Error Fetching http headers HTTP') {
                // not sure how often and why this happens...
                $this->SendDebug(__FUNCTION__, (string) $e->getMessage(), 0);
                $this->SendDebug(__FUNCTION__, $e->getFile() . ' (' . $e->getLine() . ')', 0);
                foreach ($e->getTrace() as $trace) {
                    $this->SendDebug(__FUNCTION__, $trace['file'] . ' (' . $trace['line'] . ')', 0);
                }
            } else {
                throw $e;
            }
        }
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
            $this->UpdateFormField('rinconMessage', 'caption', sprintf($this->translate('Could not connect to %s'), $ip));
            return;
        }

        $xmlr = new SimpleXMLElement($result);
        $rincon = str_replace('uuid:', '', $xmlr->device->UDN);
        if ($rincon) {
            $this->UpdateFormField('RINCON', 'value', $rincon);
        } else {
            $this->UpdateFormField('rinconMessage', 'visible', true);
            $this->UpdateFormField('rinconMessage', 'caption', sprintf($this->translate('RINCON could not be read from %s'), $ip));
        }
    }

    public function getModel(string $ip)
    {
        if ($ip) {
            $this->UpdateFormField('modelMessage', 'visible', false);
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
            $this->UpdateFormField('modelMessage', 'visible', true);
            $this->UpdateFormField('modelMessage', 'caption', sprintf($this->translate('Could not connect to %s'), $ip));
            return;
        }

        $xmlr = new SimpleXMLElement($result);
        $model = (string) $xmlr->device->displayName;
        if ($model) {
            $this->UpdateFormField('Model', 'value', $model);
        } else {
            $this->UpdateFormField('modelMessage', 'visible', true);
            $this->UpdateFormField('modelMessage', 'caption', sprintf($this->translate('Model could not be read from %s'), $ip));
        }
    }

    private function getInstanceRINCON(int $instanceID): string
    {
        if ($instanceID != $this->InstanceID) {
            $data = json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'getProperties',
                'targetInstance' => $instanceID
            ]);
            $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
            $parentResponseJSON = $this->SendDataToParent($data);
            $this->SendDebug(__FUNCTION__ . '->received from parent', $parentResponseJSON, 0);

            $RINCON = json_decode(json_decode($parentResponseJSON, true)[0], true)['RINCON'];
        } else {
            $RINCON = $this->ReadPropertyString('RINCON');
        }
        return $RINCON;
    }

    private function PauseInternal(bool $forward)
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$forward=%s', $forward ? 'true' : 'false'), 0);
        $targetInstance = $this->findTarget();

        if ($targetInstance === $this->InstanceID) {
            $sonos = $this->getSonosAccess();

            SetValue($this->GetIDForIdent('Status'), 2);
            $this->SendDebug(__FUNCTION__ . '->sonos', 'GetTransportInfo()', 0);
            if ($sonos->GetTransportInfo() == 1) {
                $this->SendDebug(__FUNCTION__ . '->sonos', 'Pause()', 0);
                $sonos->Pause();
            }
        } elseif ($forward) {
            $data = json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'callFunction',
                'targetInstance' => $targetInstance,
                'function'       => 'Pause'
            ]);
            $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
            $this->SendDataToParent($data);
        }
    } //END PauseInternal

    private function StopInternal(bool $forward)
    {
        $this->SendDebug('"' . __FUNCTION__ . '" called with', sprintf('$forward=%s', $forward ? 'true' : 'false'), 0);
        $targetInstance = $this->findTarget();

        if ($targetInstance === $this->InstanceID) {
            $sonos = $this->getSonosAccess();

            SetValue($this->GetIDForIdent('Status'), 3);
            $this->SendDebug(__FUNCTION__ . '->sonos', 'GetTransportInfo()', 0);
            if ($sonos->GetTransportInfo() == 1) {
                $this->SendDebug(__FUNCTION__ . '->sonos', 'Stop()', 0);
                $sonos->Stop();
            }
        } elseif ($forward) {
            $data = json_encode([
                'DataID'         => '{731D7808-F7C4-FA98-2132-0FAB19A802C1}',
                'type'           => 'callFunction',
                'targetInstance' => $targetInstance,
                'function'       => 'Stop'
            ]);
            $this->SendDebug(__FUNCTION__ . '->SendDataToParent', $data, 0);
            $this->SendDataToParent($data);
        }
    } //END StopInternal

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

    private function getSonosAccess(bool $writeDebug = true)
    {
        if ($this->ReadAttributeBoolean('Vanished')) {
            throw new Exception($this->Translate('Sonos Player is currently marked as "vanished" in Sonos. Maybe switched off?!'));
        }

        $ipSetting = $this->ReadPropertyString('IPAddress');
        $timeout = $this->ReadPropertyInteger('TimeOut');
        if ($writeDebug) {
            $this->SendDebug(__FUNCTION__, sprintf('IPAddress=%s ; TimeOut=%d', $ipSetting, $timeout), 0);
        }
        $ip = '';

        if ($ipSetting) {
            $ip = gethostbyname($ipSetting);
            if ($writeDebug) {
                $this->SendDebug(__FUNCTION__, sprintf('Resolved to "%s"', $ip), 0);
            }
            if ($timeout && @Sys_Ping($ip, $timeout) != true) {
                if ($writeDebug) {
                    $this->SendDebug(__FUNCTION__, 'First connect not possible', 0);
                }
                if (@Sys_Ping($ip, $timeout) != true) {
                    throw new Exception(sprintf($this->Translate('Sonos Player %s is not available, TimeOut: %s ms'), $ipSetting, $timeout));
                }
            }
        }
        return $this->getSonos($ip);
    } // End getSonosAccess

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
            $this->SendDebug(__FUNCTION__ . '->Forwarding to ', (string) $memberOfGroup, 0);
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
            'Battery'         => 16,
            'PowerSource'     => 17,
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
            'Track'           => 30,
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
