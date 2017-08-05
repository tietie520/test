<?php

namespace Phoenix\Support;

if (!defined('IN_PX'))
    exit;

use Phoenix\Log\Log4p as logger;

/**
 *        if ($this->ftp->connect()) {
 *            $this->ftp->pmkdir('/test/1/2');
 *            //$this->ftp->upload(ROOT_PATH . 'test.jpg', '/test1.jpg');
 *        }
 *        $this->ftp->close();
 */
class Ftp {

    private function __Service() {}

    private function __Bundle($ftp = 'data/ftp.cache.php') {}

    private $_connId = null;

    // --------------------------------------------------------------------

    /**
     * FTP Connect
     *
     * @access	public
     * @param	array	 the connection values
     * @return	bool
     */
    public function connect() {
        if (false === ($this->_connId = @ftp_connect($this->ftp['hostname'], $this->ftp['port']))) {
            if ($this->ftp['debug'] == true) {
                $this->_error('ftp_unable_to_connect');
            }
            return false;
        }

        if (!$this->_login()) {
            if ($this->ftp['debug'] == true) {
                $this->_error('ftp_unable_to_login');
            }
            return false;
        }

        // Set passive mode if needed
        if ($this->ftp['passive'] == true) {
            ftp_pasv($this->_connId, true);
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * FTP Login
     *
     * @access	private
     * @return	bool
     */
    private function _login() {
        return @ftp_login($this->_connId, $this->ftp['username'], $this->ftp['password']);
    }

    // --------------------------------------------------------------------

    /**
     * Validates the connection ID
     *
     * @access	private
     * @return	bool
     */
    public function _isConn() {
        if (!is_resource($this->_connId)) {
            if ($this->ftp['debug'] == true) {
                $this->_error('ftp_no_connection');
            }
            return false;
        }
        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Change directory
     *
     * The second parameter lets us momentarily turn off debugging so that
     * this function can be used to test for the existence of a folder
     * without throwing an error.  There's no FTP equivalent to is_dir()
     * so we do it by trying to change to a particular directory.
     * Internally, this parameter is only used by the "mirror" function below.
     *
     * @access	public
     * @param	string
     * @param	bool
     * @return	bool
     */
    public function changedir($path = '', $supressDebug = false) {
        if ($path == '' || !$this->_isConn()) {
            return false;
        }

        $result = @ftp_chdir($this->_connId, $path);

        if ($result === false) {
            if ($this->ftp['debug'] == true && $supressDebug == false) {
                $this->_error('ftp_unable_to_changedir');
            }
            return false;
        }

        return true;
    }

    /**
     * Create a directory
     *
     * @access	public
     * @param	string
     * @return	bool
     */
    public function mkdir($path = '', $permissions = null) {
        if ($path == '' || !$this->_isConn()) {
            return false;
        }

        $result = @ftp_mkdir($this->_connId, $path);

        if ($result === false) {
            if ($this->ftp['debug'] == true) {
                $this->_error('ftp_unable_to_makdir');
            }
            return false;
        }

        // Set file permissions if needed
        if (!is_null($permissions)) {
            $this->chmod($path, (int) $permissions);
        }

        return true;
    }

    public function pmkdir($dir = '', $permissions = null) {
        // if directory already exists or can be immediately created return true
        if ($this->isDir($dir) || @ftp_mkdir($this->_connId, $dir)) {
            if (!is_null($permissions)) {
                $this->chmod($dir, (int) $permissions);
            }
            return true;
        }
        // otherwise recursively try to make the directory
        if (!$this->pmkdir(dirname($dir))) {
            return false;
        }
        // final step to create the directory
        return @ftp_mkdir($this->_connId, $dir);
    }

    public function isDir($dir) {
        if ($dir == '' || !$this->_isConn()) {
            return false;
        }
        // get current directory
        $originalDirectory = @ftp_pwd($this->_connId);
        // test if you can change directory to $dir
        // suppress errors in case $dir is not a file or not a directory
        if (@ftp_chdir($this->_connId, $dir)) {
            // If it is a directory, then change the directory back to the original directory
            @ftp_chdir($this->_connId, $originalDirectory);
            return true;
        } else {
            return false;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Upload a file to the server
     *
     * @access	public
     * @param	string
     * @param	string
     * @param	string
     * @return	bool
     */
    public function upload($locpath, $rempath, $mode = 'auto', $permissions = null) {
        if (!$this->_isConn()) {
            return false;
        }

        if (!file_exists($locpath)) {
            $this->_error('ftp_no_source_file');
            return false;
        }

        // Set the mode if not specified
        if ($mode == 'auto') {
            // Get the file extension so we can set the upload type
            $ext = $this->_getext($locpath);
            $mode = $this->_settype($ext);
        }

        $mode = ($mode == 'ascii') ? FTP_ASCII : FTP_BINARY;

        $result = @ftp_put($this->_connId, $rempath, $locpath, $mode);

        if ($result === false) {
            if ($this->ftp['debug'] == true) {
                $this->_error('ftp_unable_to_upload');
            }
            return false;
        }

        // Set file permissions if needed
        if (!is_null($permissions)) {
            $this->chmod($rempath, (int) $permissions);
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Download a file from a remote server to the local server
     *
     * @access	public
     * @param	string
     * @param	string
     * @param	string
     * @return	bool
     */
    public function download($rempath, $locpath, $mode = 'auto') {
        if (!$this->_isConn()) {
            return false;
        }

        // Set the mode if not specified
        if ($mode == 'auto') {
            // Get the file extension so we can set the upload type
            $ext = $this->_getext($rempath);
            $mode = $this->_settype($ext);
        }

        $mode = ($mode == 'ascii') ? FTP_ASCII : FTP_BINARY;

        $result = @ftp_get($this->_connId, $locpath, $rempath, $mode);

        if ($result === false) {
            if ($this->ftp['debug'] == true) {
                $this->_error('ftp_unable_to_download');
            }
            return false;
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Rename (or move) a file
     *
     * @access	public
     * @param	string
     * @param	string
     * @param	bool
     * @return	bool
     */
    public function rename($oldFile, $newFile, $move = false) {
        if (!$this->_isConn()) {
            return false;
        }

        $result = @ftp_rename($this->_connId, $oldFile, $newFile);

        if ($result === false) {
            if ($this->ftp['debug'] == true) {
                $msg = ($move == false) ? 'ftp_unable_to_rename' : 'ftp_unable_to_move';

                $this->_error($msg);
            }
            return false;
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Move a file
     *
     * @access	public
     * @param	string
     * @param	string
     * @return	bool
     */
    public function move($oldFile, $newFile) {
        return $this->rename($oldFile, $newFile, true);
    }

    // --------------------------------------------------------------------

    /**
     * Rename (or move) a file
     *
     * @access	public
     * @param	string
     * @return	bool
     */
    public function deleteFile($filepath) {
        if (!$this->_isConn()) {
            return false;
        }

        $result = @ftp_delete($this->_connId, $filepath);

        if ($result === false) {
            if ($this->ftp['debug'] == true) {
                $this->_error('ftp_unable_to_delete');
            }
            return false;
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Delete a folder and recursively delete everything (including sub-folders)
     * containted within it.
     *
     * @access	public
     * @param	string
     * @return	bool
     */
    public function deleteDir($filepath) {
        if (!$this->_isConn()) {
            return false;
        }

        // Add a trailing slash to the file path if needed
        $filepath = preg_replace("/(.+?)\/*$/", "\\1/", $filepath);

        $list = $this->listFiles($filepath);

        if ($list !== false && count($list) > 0) {
            foreach ($list as $item) {
                // If we can't delete the item it's probaly a folder so
                // we'll recursively call delete_dir()
                if (!@ftp_delete($this->_connId, $item)) {
                    $this->deleteDir($item);
                }
            }
        }

        $result = @ftp_rmdir($this->_connId, $filepath);

        if ($result === false) {
            if ($this->ftp['debug'] == true) {
                $this->_error('ftp_unable_to_delete');
            }
            return false;
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Set file permissions
     *
     * @access	public
     * @param	string	the file path
     * @param	string	the permissions
     * @return	bool
     */
    public function chmod($path, $perm) {
        if (!$this->_isConn()) {
            return false;
        }

        // Permissions can only be set when running PHP 5
        if (!function_exists('ftp_chmod')) {
            if ($this->ftp['debug'] == true) {
                $this->_error('ftp_unable_to_chmod');
            }
            return false;
        }

        $result = @ftp_chmod($this->_connId, $perm, $path);

        if ($result === false) {
            if ($this->ftp['debug'] == true) {
                $this->_error('ftp_unable_to_chmod');
            }
            return false;
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * FTP List files in the specified directory
     *
     * @access	public
     * @return	array
     */
    public function listFiles($path = '.') {
        if (!$this->_isConn()) {
            return false;
        }

        return ftp_nlist($this->_connId, $path);
    }

    // ------------------------------------------------------------------------

    /**
     * Read a directory and recreate it remotely
     *
     * This function recursively reads a folder and everything it contains (including
     * sub-folders) and creates a mirror via FTP based on it.  Whatever the directory structure
     * of the original file path will be recreated on the server.
     *
     * @access	public
     * @param	string	path to source with trailing slash
     * @param	string	path to destination - include the base folder with trailing slash
     * @return	bool
     */
    public function mirror($locpath, $rempath) {
        if (!$this->_isConn()) {
            return false;
        }

        // Open the local file path
        if ($fp = @opendir($locpath)) {
            // Attempt to open the remote file path.
            if (!$this->changedir($rempath, true)) {
                // If it doesn't exist we'll attempt to create the direcotory
                if (!$this->mkdir($rempath) || !$this->changedir($rempath)) {
                    return false;
                }
            }

            // Recursively read the local directory
            while (false !== ($file = readdir($fp))) {
                if (@is_dir($locpath . $file) && substr($file, 0, 1) != '.') {
                    $this->mirror($locpath . $file . "/", $rempath . $file . "/");
                } elseif (substr($file, 0, 1) != ".") {
                    // Get the file extension so we can se the upload type
                    $ext = $this->_getext($file);
                    $mode = $this->_settype($ext);

                    $this->upload($locpath . $file, $rempath . $file, $mode);
                }
            }
            return true;
        }

        return false;
    }

    // --------------------------------------------------------------------

    /**
     * Extract the file extension
     *
     * @access	private
     * @param	string
     * @return	string
     */
    private function _getext($filename) {
        if (false === strpos($filename, '.')) {
            return 'txt';
        }

        $x = explode('.', $filename);
        return end($x);
    }

    // --------------------------------------------------------------------

    /**
     * Set the upload type
     *
     * @access	private
     * @param	string
     * @return	string
     */
    private function _settype($ext) {
        $text_types = array(
            'txt',
            'text',
            'php',
            'phps',
            'php4',
            'js',
            'css',
            'htm',
            'html',
            'phtml',
            'shtml',
            'log',
            'xml'
        );


        return (in_array($ext, $text_types)) ? 'ascii' : 'binary';
    }

    // ------------------------------------------------------------------------

    /**
     * Close the connection
     *
     * @access	public
     * @param	string	path to source
     * @param	string	path to destination
     * @return	bool
     */
    public function close() {
        if (!$this->_isConn()) {
            return false;
        }

        @ftp_close($this->_connId);
    }

    // ------------------------------------------------------------------------

    /**
     * Display error message
     *
     * @access	private
     * @param	string
     * @return	bool
     */
    private function _error($line) {
        logger::debug($this->_lang($line));
    }

    private function _lang($key) {
        $lang = array();
        $lang['ftp_no_connection'] = "Unable to locate a valid connection ID. Please make sure you are connected before peforming any file routines.";
        $lang['ftp_unable_to_connect'] = "Unable to connect to your FTP server using the supplied hostname.";
        $lang['ftp_unable_to_login'] = "Unable to login to your FTP server. Please check your username and password.";
        $lang['ftp_unable_to_makdir'] = "Unable to create the directory you have specified.";
        $lang['ftp_unable_to_changedir'] = "Unable to change directories.";
        $lang['ftp_unable_to_chmod'] = "Unable to set file permissions. Please check your path. Note: This feature is only available in PHP 5 or higher.";
        $lang['ftp_unable_to_upload'] = "Unable to upload the specified file. Please check your path.";
        $lang['ftp_unable_to_download'] = "Unable to download the specified file. Please check your path.";
        $lang['ftp_no_source_file'] = "Unable to locate the source file. Please check your path.";
        $lang['ftp_unable_to_rename'] = "Unable to rename the file.";
        $lang['ftp_unable_to_delete'] = "Unable to delete the file.";
        $lang['ftp_unable_to_move'] = "Unable to move the file. Please make sure the destination directory exists.";
        return $lang[$key];
    }

}
