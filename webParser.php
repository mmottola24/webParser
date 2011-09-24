<?php

/*
 * webParser
 *
 * @author Michael Mottola <info@michaelencode.com>
 * @license MIT
 * @version 1.0
 */

class webParser {

    private $_url = '';
    private $_source = '';
    public $_source_section = '';
    private $_die_on_error = true;
    private $_show_errors = true;

    public function __construct() {

    }

    public function set_url($url) {
        $this->_url = $url;
    }

    public function get_url() {
        return $this->_url;
    }

    public function get_source() {
        return $this->_source;
    }

    public function set_source($source) {
        $this->_source = $source;
    }

    public function set_errors($errors = true) {
        $this->_show_errors = $errors;
    }

    public function scrape_page($url = '') {

        if (empty($url)) {
            $url = $this->get_url();

            if (empty($url)) {
                $this->write_error("No URL has been passed or been previously set.<br><br>Use set_url( [string] ) to tell me what page to scrape");
            }
        }

        $file_contents = $this->read_file($url);

        if ($file_contents === false) {
            $this->write_error('File Could not be read: ' . $url);
        }

        $this->set_source($file_contents);

        return $file_contents;

    }

    function scrape_snippet($id, $options = array()) {

        $html = (!empty($options['html'])) ? $options['html'] : '';
        $offset = (!empty($options['offset'])) ? $options['offset'] : 0;

        if (empty($html)) {
            $html = $this->_source_section;

            //if still empty than get page source
            if (empty($html)) {
                 $html = $this->get_source();
            }

            //if still empty than scrape page
            if (empty($html)) {
                $html = $this->scrape_page();
            }
        }

        $element_tag = '';

        switch (substr($id, 0, 1)) {
            case '.' :
                $search_str = 'class="' . substr($id, 1) . '';
                break;
            case '#' :
                $search_str = 'id="' . substr($id, 1) . '';
                break;
            default :
                $search_str = '<' . $id;
                break;
        }


        $start_tag_pos = strpos($html, $search_str);

        if (substr($search_str, 0, 1) !== '<') {

            $i = $start_tag_pos;
            $found = false;
            $pos_of_next_space = 0;

            while ($found == false) {

                if (substr($html, $i, 1) == '<') {
                    $found = true;
                    $start_tag_pos = $i;
                } else if (substr($html, $i, 1) == ' ') {
                    $pos_of_next_space = $i;
                }

                $i--;
            }

            $element_tag = trim(substr($html, $start_tag_pos + 1, $pos_of_next_space - $start_tag_pos));

        } else {
            $element_tag = $id;
        }

        //remove all html before opening tag
        $old_html = $html;
        $html = substr($html, $start_tag_pos);

        //find end tag
        $end_tag_pos = $this->find_end_tag_pos($html, $element_tag);

        $html = substr($html, 0, $end_tag_pos);

        if ($offset > 0) {
            $old_html = str_replace($html, '', $old_html);
            $html = $this->scrape_snippet($id, array('html'=>$old_html, 'offset'=>$offset - 1));
        }

        $this->_source_section = $html;

        return $html;
    }

    private function find_end_tag_pos($html, $element_tag) {
        //compare the positions of next start tag and end tag

        $found = false;
        $element_tag = trim($element_tag); //safely first


        $pos_start_tag = 1;
        $pos_end_tag = 1;

        $start_tag_offset = strlen($element_tag) + 2;
        $end_tag_offset = strlen($element_tag) + 3;

        $i = 0;

        while ($found == false) {

            $pos_start_tag = strpos($html, '<' . $element_tag, $pos_start_tag + $start_tag_offset);

            $prev_end_tag = $pos_end_tag;
            $pos_end_tag = strpos($html, "</" . $element_tag . ">", $pos_end_tag + $end_tag_offset);

            if ($pos_end_tag == 0) {
                $pos_end_tag = $prev_end_tag;
            }

            /* Handy Debugging Prints
            echo '<pre>';
            print_r($pos_start_tag . ' - ' . urlencode(substr($html, $pos_start_tag, 40)));
            print_r($pos_end_tag . ' - ' . urlencode(substr($html, $pos_end_tag, 40)));
            print_r('==========================================');
            echo '</pre>';
            //*/

            if ($pos_start_tag >= $pos_end_tag || $pos_start_tag == 0) {
                //$html = substr($html, 0, $pos_end_tag);
                $pos_end_tag = $pos_end_tag + $end_tag_offset;
                $found = true;
            }

            $i++;

        }

        return $pos_end_tag;

    }

    /**
     * Read Remote File
     * @param $file
     * @return bool|string
     */
    public function read_file($file) {

        //using file_get_contents
        /*
        if (!empty($file)) {
            $data = file_get_contents($file);
            if ($data !== false) {
                return $data;
            }
        }
        return false;
        */

        //using Curl
        if (!empty($file)) {

            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $file);

                // don't give me the headers just the content
                curl_setopt($ch, CURLOPT_HEADER, 0);

                // return the value instead of printing the response to browser
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

                // use a user agent to mimic a browser
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:2.0) Gecko/20100101 Firefox/4.0');

                $data = curl_exec($ch);

                // remember to always close the session and free all resources
                curl_close($ch);

                if (!empty($data)) {
                    return $data;
                } else {
                    return false;
                }
            }
        }
    }

    protected function parse_html($id, $options = array()) {
        $html = $options['html'];

        /* Options */
        if (empty($options['html'])) {
            $html = $this->get_html($id, $options);
        }
        $element_type = $this->get_element_tag($html);

        switch ($element_type) {
            case 'table' :
                return $this->parse_html_table($html, $options);
                break;
            case 'dl' :
                break;
            case 'ul' :
            case 'ol' :
                break;
            case 'select' :
                break;
            default :
                $this->print_soft_error('Unable to parse selected tag');
                break;
        }

    }

    public function element_to_json($id, $options = array()) {
        $options['output'] = 'json';
        return $this->parse_html($id, $options);
    }

    public function element_to_array($id, $options = array()) {
        $options['output'] = 'array';
        return $this->parse_html($id, $options);
    }

    protected function get_element_tag($html = '') {

        if (empty($html)) {
            $html = $this->get_html();
        }

        $tag_name = '';

        if (substr($html, 0, 1) == '<') {
            $tag_name = substr($html, 1, (strpos($html, ' ') - 1));
        }

        return trim($tag_name);
    }

    protected function parse_html_table($html, $options = array()) {

        //set default options
        if (empty($options['output'])) {
            $options['output'] = 'array';
        }

        $data = array(); //array to be converted to json
        $headers = array();

        preg_match_all("'<tr(.*?)</tr>'si", $html, $table_rows);

        //pr($table_rows);

        if (!empty($table_rows)) {
            pr($options);
            foreach ($table_rows[0] as $i => $row) {

                preg_match_all("'<td(.*?)</td>'si", $row, $cells);

                if (!empty($cells)) {
                    foreach ($cells[0] as $j => $cell) {

                        // @todo: add check to see if colspan exists in table cell attribute

                        $cell = preg_replace(array("'<td(.*?)>'si","'</td>'"), '', $cell); //give me all character between the opening tag and ending tag

                        $cell = trim(strip_tags($cell));

                        if ($i === 0 && $options['use_first_as_keys'] != false && empty($options['fields'])) {
                            //is first row and first row is set to be the headers
                            if (empty($cell)) {
                                $cell = 'null';
                                if (!empty($options['blank_cell_value'])) {
                                    $cell = $options['blank_cell_value'];
                                }
                            }
                            $cell = str_replace('&nbsp;','', $cell);
                            $headers[$j] = trim(strtolower($cell));

                        } else {
                            if (isset($options['fields']) && !empty($options['fields'][$j])) {
                                $data[$i][$options['fields'][$j]] = trim($cell);
                            } elseif (!isset($options['fields'])) {
                                $data[$i][] = trim($cell);
                            }
                        }


                    }

                } else {
                    $this->print_soft_error('No Cells Found');
                }

            }
        }

        if ($options['output'] == 'array') {
            return $data;
        } else if ($options['output'] == 'json') {
            return json_encode($data);
        }

    }

    public function save_file($filename, $data) {
        $new_file = @file_put_contents($filename, $data);

        if (empty($new_file)) {
            $this->print_soft_error('Unable to Save to File');
            return false;
        }

        return true;
    }

    public function clear() {
    	$this->_source_section = '';
    	$this->_source = '';
    }

    private function get_html($id = '', $options = array()) {

        if (!empty($id)) {
            $html = $this->scrape_snippet($id, $options);
        } else {
            $html = $this->_source_section;
        }

        if (empty($html)) {
            $html = $this->_source;

            if (empty($html)) {
                $html = $this->scrape_snippet($id, $options);
            }

            if (empty($html)) {
                $this->write_error('Found no source to Scrape.');
            }
        }

        return $html;
    }

    private function write_error($msg) {
        if ($this->_show_errors) {
            $msg =  '<b>Error:</b> ' . $msg . '<br>';

            if ($this->_die_on_error === true) {
                die($msg);
            } else {
                return $msg;
            }
        }
    }

    private function print_soft_error($msg) {
        if ($this->_show_errors) {
            $msg =  '<b>Error:</b> ' . $msg . '<br>';
            echo $msg;
            return false;
        }
    }


}
?>
