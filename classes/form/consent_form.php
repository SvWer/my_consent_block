<?php

/**
 * Version details.
 *
 * @package    my_consent_block
 * @author	   Sven
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/formslib.php");

class consent_form extends moodleform {
    
    function definition() {
        global $CFG;
        
        $choice = $this->_customdata['agreedis'];
        
        $mform = $this->_form; // Don't forget the underscore!
        $radioarray = array();
        $radioarray[0] = $mform->addElement('radio', 'agreedis', '', get_string('disagree', 'block_my_consent_block'), '0');
        $radioarray[1] = $mform->addElement('radio', 'agreedis', '', get_string('agree', 'block_my_consent_block'), '1');
        //$mform->addGroup($radioarray, 'radiogroup');
        $mform->setDefault('agreedis',$choice);
        
        $this->add_action_buttons();
    }                           // Close the function
}                               // Close the class