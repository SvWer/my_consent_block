<?php

/**
 * Version details.
 *
 * @package    my_consent_block
 * @author	   Sven
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/formslib.php");

class mail_form extends moodleform {
    
    function definition() {
        global $CFG;
        
        $default_mail = $this->_customdata['emailtext'];
        
        $mform = $this->_form; // Don't forget the underscore!
        $mform->addElement('text', 'emailtext', 'Email');
        $mform->setType('emailtext',PARAM_EMAIL);
        $mform->setDefault('emailtext', $default_mail);
        
        $this->add_action_buttons(false);
    }                           // Close the function
}                               // Close the class