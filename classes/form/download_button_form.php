<?php

/**
 * Version details.
 *
 * @package    my_consent_block
 * @author	   Sven
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/formslib.php");

class download_button_form extends moodleform {
    
    function definition() {
        global $CFG;
        
        $mform = $this->_form; // Don't forget the underscore!
        //$mform->addElement('button', 'intro', get_string("buttonlabel"));
        $mform->addElement('submit', 'submitbutton', 'Daten exportieren');
    }                           // Close the function
}                               // Close the class

