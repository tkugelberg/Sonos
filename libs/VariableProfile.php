<?php

trait VariableProfile
{

    private function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize)
    {

        if (!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, 1);
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if ($profile['ProfileType'] != 1)
                throw new Exception("Variable profile type does not match for profile " . $Name);
        }

        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
    }

    private function RegisterProfileIntegerEx($Name, $Icon, $Prefix, $Suffix, $Associations)
    {
        if (sizeof($Associations) === 0) {
            $MinValue = 0;
            $MaxValue = 0;
        } else {
            $MinValue = $Associations[0][0];
            $MaxValue = $Associations[sizeof($Associations) - 1][0];
        }

        $this->RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);

        foreach ($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }
    }

    private function removeVariable($name, $links)
    {
        $vid = @$this->GetIDForIdent($name);
        if ($vid) {
            // delete links to Variable
            foreach ($links as $key => $value) {
                if ($value['TargetID'] === $vid)
                    IPS_DeleteLink($value['LinkID']);
            }
            foreach (IPS_GetChildrenIDs($vid) as $key => $cid) {
                if (IPS_EventExists($cid)) IPS_DeleteEvent($cid);
            }
            $this->UnregisterVariable($name);
        }
    }

    private function removeVariableAction($name, $links)
    {
        $vid = @$this->GetIDForIdent($name);
        if ($vid) {
            // delete links to Variable
            foreach ($links as $key => $value) {
                if ($value['TargetID'] === $vid)
                    IPS_DeleteLink($value['LinkID']);
            }
            foreach (IPS_GetChildrenIDs($vid) as $key => $cid) {
                if (IPS_EventExists($cid)) IPS_DeleteEvent($cid);
            }
            $this->DisableAction($name);
            $this->UnregisterVariable($name);
        }
    }
}
