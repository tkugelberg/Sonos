# Changelog
### Version 2.6
- added Move 2, Roam 2, Era 100 and Era 300

### Version 2.5
- changes for visualization for media player
- preparation for 7.0

### Version 2.4
- Add "Ray"

### Version 2.3
- correct MediaFile Handling and cover determination
- Add "USB Power" as power source

### Version 2.2
- Add support for Move and Roam
- Add PowerSource and BatteryStatus Move and Roam

### Version 2.1
- some error handling improvement
- Add function GetMembers
- Add function SetTrack

### Version 2.0
- complete refactoring for adding to Symcon Module Store
- Discovery instance added for automatic finding of Sonos products
- Splitter Instance added for
  - central configuration
  - grouping
  - interaction between different Sonos instances
- ForceGrouping was replaced by RejoinGroup
  - In cases a Sonos instance is marked as "vanished" in Sonos, it can be added to its last group when being available again
- Handling of Vanished
  - In case a Sonos instance is marked as "vanished" in Sonos, it will be hidden in Symcon and unhidden once it is visible again
- Radio Configuration
  - delivered radio stations were removed
  - radio stations can now be maintained individually in splitter instance
- Variables for "Coordinator" and "Group Members" are now modeled as attributes
- If configuration was activated, a media object will be created, filled with the content of "Cover URL"
- translation was introduced
- added the possibility to switch off the automatic hiding of variables
- added debug messages
- Added handling of special features only some products provide
- Added MightMode and Dialog Level for Playbar, Playbase, Beam and Arc

### Version 1.08
- Added "Radio Zürichsee" and "Antenne Brandenburg"
- Added a Poperty for Album Art
 
### Version 1.07
- switched to string for files in PlayFiles --> json_encode now needed
- switched to string for files and instances in PlayFilesGrouping --> json_encode now needed

### Version 1.6
- Changed version to 1.6 to make it better visibe in Module overview
- removed bug due to change in XML (thanks danielrdt)
- disabled message "Sonos instance 192.168.1.32 is not available" in scripts
- "Radio K.W." removed

### Version 1.5.13
- also correct bug on windows, where gethostbyname('') returns "localhost"

### Version 1.5.12
- correct error in last bugfix which lead to dump in creating new instance

### Version 1.5.11
- make Sonos version 9.1 work again

### Version 1.5.10
- added function SetHdmiInput( )

### Version 1.5.9
- added function DelegateGroupCoordinationTo( )

### Version 1.5.8
- Added "Radio BOB!" and "AC/DC Collection"
- corrected regex for play files

### Version 1.5.7
- Added SNS_SetTransportURI( )
- added Crossfade and Transportsettings to _updatStatus

### Version 1.5.6
- Merge from mrworta for "Fix occasional playback loop"

### Version 1.5.5
- Add support for Favorites

### Version 1.5.4
- minor fix in _updateGrouping in case a Sonos Box is not known in IPS

### Version 1.5.3
- Add timeout during Instance creation
- add function alexaResponse( )
- add type hints

### Version 1.5.2
- UTF-8 for status buttons
- allw https for play files

### Version 1.5.1
- Changed  URL of Antenne Thüringen and Radio TOP40

### Version 1.5.0
- Ignoring Exception 'Error during Soap Call: UPnPError s:Client 701 (ERROR_AV_UPNP_AVT_INVALID_TRANSITION)' when pausing during PlayFiles
- INCOMPATIBLE CHANGE: Do not execute Play() within SetRadio, SetPlaylist, etc.

### Version 1.4.9
- Setting Playlist for one second to give feedback on WebFront

### Version 1.4.8
- Added duplicate ping check

### Versuon 1.4.7
- correct Cover URL in case it is an absolute URL (do not add Sonos host in front)
- ignore exceptions when SEEK is throwing an exception during PlayFiles (e.g. when playing Amazon Streams)

### Version 1.4.6
- also make "update Status Frequency" configurable
- also make "update Status Frequency when Instance is not available" configurable
- also make "update Grouping Frequency when Instance is not available" configurable

### Version 1.4.5
- remove unwanted leg messages by deleting last line in _updatStatus

### Version 1.4.4
- reduce the frequency of update calls if Box is not available...
  - 5 -> 300 Seconds for update Status
  - 120 -> 900 Seconds for update grouping
- also update "CoverURL" if image is read from radiotime
- save StationID
- Only lookup cover on radiotime when StationID changes
- add event to clear StationID 5 minutes past the hour

### Version 1.4.3
- fix "ERROR_AV_UPNP_AVT_INVALID_TRANSITION" wenn PlayFiles auf eine Box ausgeführt wird, die sich in einer Gruppe befindet. 

### Version 1.4.2
- Minor fix to PlayFiles since on some radio stations "TRACK" is > 1...

### Version 1.4.1
- Hinzufügen der Möglichkeit auch importierte Playlisten zu importieren.
  - Boolean Property "Enable Playlist Control" nach Ineger "Import Playlists" geändert
  - mit den Werten 0 (kein import), 1 (saved Playlists), 2 (imported Playlists) und 3 (beides)
- Die Funktion SetPlaylist kann jetzt auch importierte Playlisten abspielen
  - schaut immer zusert in den gespeicherten, dann in den importierten Playlists nach
  - egal wie der Parameter "Import Playlists" gesetzt ist
  - bestimmte strings werden ersetzt
    - ".m3u" und ".M3U" am ende wird gelöscht
    - "_" wird duch " " ersetzt
    - Wenn z.B. die Playliste 1_test.m3u abgespielt werden solln benötigt man das Kommando SNS_SetPlaylist(12345,"1 test"); 

### Version 1.4.0
- Verbesserung der DetailsHTML 
  - Vorschlag von dansch übernmommen, Danke.
- RampToVolume hinzugefügt
  - SNS_RampToVolume($InstanceID,$rampType, $volume);
  - $rampType kann String oder Integer sein
    - 1 entspricht SLEEP_TIMER_RAMP_TYPE
    - 2 entspricht ALARM_RAMP_TYPE
    - 3 entspricht AUTOPLAY_RAMP_TYPE
- Doku um neue/vergessene Funktionen erweitert
- neue Funktion SNS_PlayFilesGrouping(integer $InstanceID, array $instances, array $files, $volume)
  - Autotomatisches Gruppieren der Instanzen 
  - Dateien abspielen
  - Ursprünglichen Zustand wiederherstellen
  - Lautärke anpassen
- planet radio hinzugefügt

### Version 1.3.5
- WDR2 BI hinzugefügt
- Radio Hochstift hinzugefügt
### Version 1.3.4
- Fix bei Detailed Status wenn ANAOLG oder SPDIF ausgewählt --> kein HTML erzeugen

### Version 1.3.3
- Fix bei PlayFiles wenn ANAOLG oder SPDIF ausgewählt --> "NOT_IMPLEMETED", schon wieder!

### Version 1.3.2
- Fix wenn TrackDuration keine Zeit enhält, sondern "NOT_IMPLEMETED"
  - Tritt auf z.B., wenn als Input ANAOLG oder SPDIF ausgewählt ist


### Version 1.31
- Fix für "devision by zero" wenn SPDIF (und wohl auch Analog) als input gewählt ist.

### Version 1.3
- Das Profil für Gruppen wird nun bei jedem speichern der Konfiguration einer Instanz neu erzeugt.
  - dies hilft beim aufräumen von fragmenten bereits gelöschter Instanzen
  - Potentielle Fehler/Hickups werden bereiningt.
- PlayMode hinzugefügt, umfasst auch Crossfade
  - 0: "NORMAL"
  - 1: "REPEAT_ALL"
  - 2: "REPEAT_ONE"
  - 3: "SHUFFLE_NOREPEAT"
  - 4: "SHUFFLE"
  - 5: "SHUFFLE_REPEAT_ONE"
- Die Option "Enable detailed info" hinzugefügt
  - Beinhaltet die Variablen
    - Details
    - CoverURL
    - ContentStream
    - Artist
    - Title
    - Album
    - TrackDuration
    - Position
  - automatisches Füllen der Variablen
  - ersatellen einer HTML-Box in der Details Vaiablen

- Die Option "Force Variable order"
  - Diese Option bewirkt, dass die Sortiertreihenfolge auf jeden Fall so eingerichtet wird, wie von dem Modul vorgesehen.
  - Weiterhin wurde die vorgeschlagene Reihenfolge angepasst, um die Detaillierten Infos besser anzeigen/einsortieren zu können
  - wenn aktiviert, wird bei jedem ApplyChanges (also auch beim update und starten von IPS) sichergestellt, dass die Sortierreihenfolge stimmt.

### Version 1.2
- Beheben eines Fehlers, durch den keine neuen Instanzen angelegt werden konnten.

### Version 1.1
- Einfürung der Versionierung ;-)
- Ermittlung der RINCON ins ApplyChanges() verlagert
  -  Wird jetzt automatisch gefüllt, wenn das Feld in der Konfigutration leer ist
  -  Manuelles Update jetzt möglich mit der Funktion "SNS_UpdateRINCON(<InstanceID>);"
- Fehlerhandling in _updateGrouping
  - exception wenn die RINCON des Gruppen Koordinators nicht bekannt ist
- Property "Update Grouping Frequency" eingeführt
   - häufigkeit der Ausführung des  _updateGrouping Skriptes
- Defauling von "Stations in WebFront" auf leer
- Default für "Include TuneIn favorites" auf true
- Gruppenkonzept komplett überarbeitet
  - Coordinator kann jetzt nicht mehr in der Instanzkonfiguration gesetzt werden, sondern wird dynamisch ermittelt
  - Die Annahme, dass eine Gruppenrincon immer die RINCON des Koordinators enthält ausgebaut
  - Profile Association der verfügbaren Gruppen wird dynamisch angepasst (alle Koordinatoren können als Gruppe gewählt werden)
  - DeleteSleepTimer, Next, Pause, Play, Previous, SetSleepTimer und Stop werden jetzt nur noch auf Koordinatoren ausgeführt
    - Wenn derf Koordinator ermittelt werden kann (sollte der Regelfall sein) wird das Kommando automatisch an den Gruppenkoordinator weitergeleitet
    - wenn der Gruppenkoordinator nicht ermittelt werden kann (eigentlich nur dann möglich, wenn nicht alle Sonos Boxen in IPS bekannt sind), wird ein Fehler geworfen.
- Es werden jetzt exceptions geworfen, wenn die Instanz bei einem _updateStatus oder _updateGrouping als nicht verfügbar angesehen wird.
- Default Timeout auf "1000" hochgesetzt