<?php

class UrlRedirectDb
{
    private $_hostname = null;
    private $_username = null;
    private $_password = null;
    private $_database = null;
    private int $_portnum = 3306;

    private $_dbHandle;
    private bool $_externalConnection = false;

    function __construct(array|\mysqli $dbInfoArr) {
        if ($dbInfoArr instanceof \mysqli) {
            $this->_dbHandle = $dbInfoArr;
            $this->_externalConnection = true;
            return;
        }

        if (sizeof($dbInfoArr) < 4 || sizeof($dbInfoArr) > 5) {
            throw new InvalidArgumentException("Required database configuration keys (host, login, pass, database) are missing.");

        }

        $this->_hostname = $dbInfoArr['host'];
        $this->_username = $dbInfoArr['login'];
        $this->_password = $dbInfoArr['pass'];
        $this->_database = $dbInfoArr['database'];

        if (isset($dbInfoArr['port'])) {
            $this->_portnum = $dbInfoArr['port'];
        }

        return ;
    }

    function getRedirectUrl($redirector) {
        if ($redirector->getShort()) {
            $this->_openHandle();
            
            $longUrl = $this->_retrieveRedirectUrlFromDb($redirector->getShort());
            $this->_updateDbLogForRedirect($redirector->getShort());
            
            $this->_closeHandle();

            return $longUrl;
        }
        return false;
    }

    function getAllLogEntries($count = null) {
        $this->_openHandle();

        $logEntries = $this->_retrieveAllLogEntriesFromDb($count);

        $this->_closeHandle();

        return $logEntries;
    }

    function getTopShorts($count = null) {
        $this->_openHandle();

        $logEntries = $this->_retrieveTopShorts($count);

        $this->_closeHandle();

        return $logEntries;
    }

    function getAllShorts($count = null) {
        $this->_openHandle();

        $logEntries = $this->_retrieveAllShorts($count);

        $this->_closeHandle();

        return $logEntries;
    }

    function setRedirectUrl($redirector) {
        if ($redirector->getShort() && $redirector->getLong()) {
            $this->_openHandle();

            $result = $this->_postRedirectToDb($redirector);
            $this->_closeHandle();

            return $result;
        }
        return false;
    }

    function updateRedirectUrl($redirector) {
        if ($redirector->getShort() && $redirector->getLong()
            && $redirector->getUser()) {

            $this->_openHandle();

            $result = $this->_updateRedirectUrl($redirector);
            $this->_closeHandle();

            return $result;
        }
        return false;
    }

/***** Private Methods ******************************************************/

    private function _updateRedirectUrl($redirector) {
        $query  = "UPDATE redirects ";
        $query .= "SET redirect_url = '" . $redirector->getLong() . "' ";
        $query .= "WHERE short = '" . $redirector->getShort() . "' AND ";
        $query .= "user = '" . $redirector->getUser() . "'";

        if ($this->_dbHandle->query($query) === true) {
            return true;
        }

        return false;
    }

    private function _postRedirectToDb($redirector) {
        $query  = "INSERT INTO redirects VALUES";
        $query .= "('" . $redirector->getShort() . "','" . $redirector->getLong() . "',NOW(),0,'" . $redirector->getUser() ."')";

        if ($this->_dbHandle->query($query) === true) {
            return true;
        }
        return false;
    }

    private function _retrieveTopShorts($count) {
        $query  = "SELECT log.redirect_key AS short";
        $query .= ", COUNT(log.date_used) AS count";
        $query .= ", main.redirect_url AS url";
        $query .= ", main.user AS user";
        $query .= " FROM redirect_log log ";
        $query .= " JOIN redirects main ON log.redirect_key = main.redirect_key";
        $query .= " GROUP BY short";
        $query .= " ORDER BY count DESC";

        if ($count) {
            $query .= " LIMIT $count";
        }

        $result = $this->_dbHandle->query($query);
        if ($result) {
            $resArr = $result->fetch_all(MYSQLI_ASSOC);

            if (is_array($resArr)) {
                return $resArr;
            }
        }
        return null;
    }

    private function _retrieveAllShorts($count) {
        $query = "SELECT main.redirect_key AS short";
        $query .= ", main.user AS user";
        $query .= ", main.redirect_url AS url";
        $query .= ", main.created_at AS created";
        $query .= " FROM redirects main";
        $query .= " ORDER BY created DESC";

        if ($count) {
            $query .= " LIMIT $count";
        }

        $result = $this->_dbHandle->query($query);
        if ($result) {
            $resArr = $result->fetch_all(MYSQLI_ASSOC);

            if (is_array($resArr)) {
                return $resArr;
            }
        }
        return null;
    }

    private function _retrieveAllLogEntriesFromDb($count) {
        $query  = "SELECT log.redirect_key AS short";
        $query .= ", log.date_used AS date";
        $query .= ", main.redirect_url AS url";
        $query .= ", main.user AS user";
        $query .= " FROM redirect_log log ";
        $query .= " JOIN redirects main ON log.redirect_key = main.redirect_key";
        $query .= " ORDER BY date DESC";

        if ($count) {
            $query .= " LIMIT $count";
        }

        $result = $this->_dbHandle->query($query);
        if ($result) {
            $resArr = $result->fetch_all(MYSQLI_ASSOC);

            if (is_array($resArr)) {
                return $resArr;
            }
        }
        return null;
    }


    private function _retrieveRedirectUrlFromDb($abbreviation) {
        $query = "SELECT redirect_url FROM redirects WHERE redirect_key = '$abbreviation'";

        if ($stmt = $this->_dbHandle->prepare($query)) {
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $row = $result->fetch_assoc()) {
                return $row['redirect_url'];
            }
        }

        return null;
    }

    private function _updateDbLogForRedirect($abbreviation) {
        $query  = "INSERT INTO redirect_log VALUES('$abbreviation', NOW())";

        if ($this->_dbHandle->query($query) === true) {
            return true;
        }

        return false;
    }

    private function _fieldsFilled() {
        if ($this->_hostname && $this->_username && $this->_password && $this->_database && $this->_portnum) {
            return true;
        }
        return false;
    }

    private function _openHandle() {
        if ($this->_externalConnection) {
            return true;
        }

        if ($this->_fieldsFilled()) {
            $this->_dbHandle = mysqli_connect(
                $this->_hostname,
                $this->_username,
                $this->_password,
                $this->_database,
                $this->_portnum
            ) or die('Graceless DB failure connecting!');
            return true;
        }
        return false;
    }

    private function _closeHandle() {
        if ($this->_externalConnection) {
            return;
        }

        $this->_dbHandle->close();
    }
}
