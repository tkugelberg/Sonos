<?php

declare(strict_types=1);

if (defined('PHPUNIT_TESTSUITE')) {
    trait GetSonos
    {
        private $SonosDouble = '';

        public function setSonos($SonosDouble)
        {
            $this->SonosDouble = $SonosDouble;
        }
        private function getSonos(string $ip)
        {
            $this->SonosDouble->SetIP( $ip );
            return $this->SonosDouble;
        }
    }
} else {
    trait GetSonos
    {
        private function getSonos(string $ip)
        {
            return new SonosAccess($ip);
        }
    }
}