<?php

class UrlRedirector
{

    private $_abbreviation   = null;
    private $_longUrl        = null;
    private $_user           = null;

    function __construct($abbrev = null, $url = null) {
        $short = $this->setShort($abbrev);
        $long = $this->setLong($url); 

        if ($short && $long) {
            return true;
        }
        
        throw new InvalidArgumentException("Invalid arguments sent to constructor.");
    }

    function setShort($abbrev) {
        $abbrev = trim($abbrev);
        // This set of shenanigans is because of FB adding a querystring to the end of things
        // It's an ugly solution...TODO: do this right
        $abbrev = explode("?", $abbrev);
        $abbrev = is_array($abbrev) ? $abbrev[0] : $abbrev;
        
        if ($this->_isValidAbbreviation($abbrev)) {
            $this->_abbreviation = $abbrev;
            return $this->_abbreviation;
        }
        return null;
    }

    function setLong($url) {
        $url = trim($url);
        if ($this->_isValidUrl($url)) {
            $this->_longUrl = $url;
            return $this->_longUrl;
        }
        return null;
    }

    function setUser($user) {
        if ($user) {
            $this->_user = $user;
            return $this->_user;
        }
        return null;
    }

    function getShort() {
        return $this->_abbreviation;
    }

    function getUser() {
        return $this->_user;
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

        if (preg_match($shortRegex, (string) $abbrev)) {
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

