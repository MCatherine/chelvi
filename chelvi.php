<?php
  /**
   This file is part of Chelvi, Chelvi is a cute PHP5 framework.
   Copyright (C) 2009  Aravinda VK <hallimanearavind AT gmail DOT com>

   This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>.
  */

  /**
   * Chelvi is object oriented cute PHP framework
   * @author Aravinda VK (hallimanearavind AT gmail DOT com)
   * @version 0.0.2
   * @date 2009-05-23
   */

// Version 
define('CHELVI_VERSION','0.0.2');

// Global settings for error and exception handling
error_reporting(0);
set_error_handler(array('chelvi','ohError'), E_ALL^E_NOTICE);
set_exception_handler(array('chelvi','ohException'));

// Class with static methods
class chelvi{
    // Static variables required for chelvi
    static $userObject;
    static $urls;
    static $templateValues = array();
    static $customLogs = array();
    static $home='home';
    static $error='error';
    static $key = 'o';
    static $numberOfInstances = 0;

    /*
     * This function helps in getting the version Number
     *
     * @description Prints the version number
     * Usage:  echo chelvi::version();
     * 
     * @param None
     * @return Displays version number
     */
    function version(){
        return CHELVI_VERSION;
    }

    /**
     * maps Every valid URL to function, redirect to error page if not exists
     *
     * @description Redirects every valid URL to respective function.
     *
     * @param $urls A array containing list of valid menu items
     * @param $keyName GET variable name configured in .htaccess file
     * @return Displays the output from the various functions
     */
    function start($object) {
        // To check only once this function is called 
        if(self::$numberOfInstances == 0){
            
            // If input is not an object
            if(!is_object($object)){
                die('Not an valid object');
            }

            // Cloning the object which is required in other functions
            self::$userObject = clone $object;

            // Getting value of key(GET variable name)
            if(isset($object->key)){
                self::$key = $object->key;
            }

            // Getting the list of URL's allowed(List of exposed functions)
            self::$urls = get_class_methods($object);

            // Getting the default names for home and error functions
            if(defined('DEFAULT_HOME_FUNC')) {
                self::$home = DEFAULT_HOME_FUNC;
            }
            if(defined('DEFAULT_ERROR_FUNC')) {
                self::$error = DEFAULT_ERROR_FUNC;
            }
            
            // For Local use
            $home = self::$home;

            // For validating Number of arguments passed
            $getVars = array_keys($_GET);
            $numberOfGetVars = count($getVars);

            // If no arguments are passed, default function to call is home()
            if($numberOfGetVars == 0) {
                if(in_array($home,self::$urls)){
                    echo $object->$home();
                }
                else{
                    throw new Exception('Function home() or equivalent '.
                                        'function is not defined in '.
                                        'your project '.
                                        'class (may be in index.php)');
                }
            }
            // If Valid GET parameter
            else if($numberOfGetVars == 1 && $getVars[0] == self::$key) {

                // Removes '/' from the end of GET variable, and splits
                // into array.
                $options = explode('/',
                                   preg_replace('/\/$/','',$_GET[self::$key]));

                // validate the input(Check is it in declared array of URLS)
                if(in_array($options[0], self::$urls)){
                    // Call respective user function
                    echo call_user_func_array(array($object,$options[0]),
                                              array_slice($options,1));
                }
                else{
                    echo self::errorHandle('404');
                }
            }
            // If more than one GET variables are used
            else {
                echo self::errorHandle('404');
            }

            // will check custom display function is enabled or not
            // and displayes messages if enabled.
            self::displayCustomLogMessages();

            // Will be incremented so that only once this function
            // is called in code
            self::$numberOfInstances++;
        }
        else{
            // If instances more than one
            exit;
        }
    }

    /**
     * @description This will check existance of the user error handling
     * function before calling.
     *
     * @param $errorFuncName Name of the user defined error function
     * @param $error Error code to pass
     * @return Calls respective error function if exist,else throws an Exception
     */
    private function errorHandle($errorId){
        $error = self::$error;

        if(in_array($error,self::$urls)){
            return self::$userObject->$error($errorId);
        }
        else {
            throw new Exception('Function error() or equivalent '.
                                'function is not defined in your '.
                                'project class'.
                                '(may be in index.php)');
        }
    }

    /**
     * @description Displays Custom log messages if DISPLAY_ERRORS_LOGS enabled
     *
     * @param None
     * @return Displays the custom log messages
     */
    private function displayCustomLogMessages(){

        // If custom log msgs to be displayed
        if(defined('DISPLAY_ERRORS_LOGS') and DISPLAY_ERRORS_LOGS == True
           and count(self::$customLogs)>0){

            // To apply some styles specific to log files
            self::customLogStyle();
            
            echo '<div style="clear:both;height:10px;"></div>';
            echo '<div id="chelviCustomLogButton">';
            echo '<a href="#chelviCustomLogs">See Logs</a></div>';
            echo '<div id="chelviCustomLogs"><h3>Custom Log Messages</h3>';

            foreach(self::$customLogs as $eachLog){
                echo '<p>'.$eachLog.'</p>';
            }

            echo '</div>';
        }
        return;
    }

    /**
     * @description Assigns values to template variables
     *
     * @param $templateVarName Template Variable Name
     * @param $value Value of the Template variable
     * @return None
     */
    function set($templateVarName,$value){
        self::$templateValues[$templateVarName] = $value;
        return;
    }

    /**
     * @description Render the output and return or echo based on input
     *
     * @param $templateFile Template File Name
     * @param $echoOrReturn If 'r' returns the output, else prints the output
     *        Default value is 'r'
     * @return Returns rendered output if the option is 'r' else returns the
     *        control to caller.
     */
    function render($templateFile, $echoOrReturn='r'){
        extract(self::$templateValues);
        if($echoOrReturn == 'r'){
            ob_start();
            require_once($templateFile);
            $op = ob_get_contents();
            ob_end_clean();
            return $op;
        }
        else{
            require_once($templateFile);
            return;
        }
    }

    /**
     * @description Custom Logger, Logging Error/User Messages in Custom format
     *
     * @param $ExceptionOrMsg Exception object if it is exception log. else User
     *        message.
     * @param $type 'custom' for custom messages else Exception
     * @return Returns rendered output if the option is 'r' else returns the
     *        control to caller.
     */
    function log($ExceptionOrMsg, $type='custom'){

        // Set Information related to client, Error/Msg
        $logDate = '['. date(DATE_RSS). '] ';

        if($type=='custom'){
            // Format: [Custom: Message in <filename> on Line <Line Number>]
            $message = '[Custom: '.$ExceptionOrMsg.
                ' in '. __FILE__ .' on Line '.__LINE__.'] ';
        }
        else{
            // Format: [Exception: Message in <filename> on Line <Line Number>]
            $message= '[Exception: '.$ExceptionOrMsg->getMessage().
                ' in '. $ExceptionOrMsg->getFile() .
                ' on Line '. $ExceptionOrMsg->getLine() .'] ';
        }

        // Client Details
        $clientDetails = $_SERVER['REMOTE_ADDR'].' '.(isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : '').
            ' "'.$_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI'].'" '.
            ' "'.(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '').'" '.$_SERVER['HTTP_USER_AGENT'];
        
        // If custom log, all msgs will be added to array and displayed
        // in the end if DISPLAY_ERRORS_LOGS enabled
        if($type=='custom'){
            array_push(self::$customLogs,$logDate.$message.$clientDetails);
        }
        // If not custom Error
        else{
            // Display error/exception for debugging if enabled
            if(defined('DISPLAY_ERRORS_LOGS') and DISPLAY_ERRORS_LOGS == True) {
                echo '<h1 style="color:red">Error Occured</h1>';
                echo '<p style="color:red">'.$logDate.$message.$clientDetails.
                    '</p>';

                // Display Custom messages too for Debugging
                self::displayCustomLogMessages();

                // Do not continue on error
                exit;
            }
            // If DISPLAY_ERRORS_LOGS not enabled, call the default error
            // page of user. On failure this will print Error message
            else {
                // To check that function error is callable
                $error = self::$error;

                if(in_array($error,self::$urls)){
                    echo self::$userObject->$error('500');
                }
                else{
                    echo "Error Occured";
                    exit;
                }
            }
        }
        if(defined('LOG_ERRORS') and LOG_ERRORS == True){
            error_log($logDate.$message.$clientDetails,3,ERROR_LOG_FILE);
        }
        return;

    }


    /**
     * Converts errors into exceptions, which will be easy to handle
     *
     * @description Recieves error details when error occures and converts that
     * to exception.
     * @param $errno Error Number
     * @param $errstr Error String
     * @param $errfile Error File Name
     * @param $errline Error Line number
     * @return Throws exception for the input error
     */
    function ohError($errno, $errstr, $errfile, $errline ) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    /**
     * Function to handle unhandled exceptions.
     *
     * @description If user didnt handled exceptions using try and catch
     * then it will be handled in this function.
     * @param $exception Exception object which will have error details
     * @return Displays the Error if display error option is enabled, Logs Error
     * if log enabled.
     */
    function ohException($exception) {
        self::log($exception,'exception');
    }

    /**
     * @description To apply custom style for Custom log message display
     * @param None
     * @return Returns control to caller
     */
    private function customLogStyle(){
        echo '<style>
          #chelviCustomLogButton{
              position:absolute;
              z-index:6;
              top:0px;
              right:50%;
              background-color:#9999cc;
              color:black;
              padding:5px;
          }
          #chelviCustomLogButton a{
              text-decoration:none;
              color:black;
          }
          #chelviCustomLogs{
              width:100%;
          }
          #chelviCustomLogs h3{
              background-color:#9999cc;
          }
          #chelviCustomLogs p{
              border-bottom:1px dashed blue;
          }
          </style>';
        return;
    }


}


?>


