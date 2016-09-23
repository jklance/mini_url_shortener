<?php

class UrlRedirector
{

    private $_abbreviation   = null;
    private $_longUrl        = null;

    function __construct($abbrev = null, $url = null) {
        $short = $this->setShort($abbrev);
        $long = $this->setLong($url); 
        
        if ($short && $long) {
            return true;
        }
        return false;
    }

    function setShort($abbrev) {
        if ($this->_isValidAbbreviation($abbrev)) {
            $this->_abbreviation = $abbrev;
            return $this->_abbreviation;
        }
        return null;
    }

    function setLong($url) {
        if ($this->_isValidUrl($url)) {
            $this->_longUrl = $url;
            return $this->_longUrl;
        }
        return null;
    }

    function getShort() {
        return $this->_abbreviation;
    }

    function getLong() {
        return $this->_longUrl;
    }

    function getRedirectHeader() {
        if ($this->_longUrl) {
            header("HTTP/1.1 302 Found");
            header("Location: " . $this->_longUrl);
            return true;
        }
        return false;
    }

    private function _isValidAbbreviation($abbrev) {
        $shortRegex = '/^[A-za-z0-9_]{1,20}$/';

        if (preg_match($shortRegex, $abbrev)) {
            return true;
        }
        return false;
    }

    private function _isValidUrl($url) {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }
        return true;
    }
}

