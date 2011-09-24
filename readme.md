# webParser

A Tool for scraping web pages, and performing actions on the html markup such as converting an html table to a json object.

**Note:**
Using this parser requires you to know a bit about the markup of the page you wish to scrape.  
It was initially designed for parsing html tables and converting the content into a JSON file,
for the purpose of caching content for a mobile app.

##Functions

* Get HTML snippets from any elements on a page
* Convert HTML elements into an array or JSON
  * Supported on TABLE, DL, UL, OL, SELECT

  
## Examples

### Initializing Class

	include('webParser');
	$wp = new webParser();

### Setting a URL to scrape
	
	$wp->set_url('http://mysitetoscrap.com');

### Scrape a certain element referencing it by element ID

	$html = $wp->scrap_snippet('#user_profile');

## Options

* use_first_as_keys: [boolean =true] uses first element grouping as field name, ex: uses first row of table
* fields: [array] allows you to choose which columns to take and what the field translates to

### Choosing Fields
You can choose which columns from an html table you want to record.  You also can set the key name on the output

	$arr = $wp->element_to_array('table', array(
		'fields' => array(
			'0'=>'away_team',
			'1'=>'away_score',
			'3'=>'home_team',
			'4'=>'home_score',
			'5'=>'date'
		)
	)); 
	
Notice we skipped the 2nd column.
	
This Outputs:

	[0] => Array
        (
            [away_team] => Shockers
            [away_score] => 2
            [home_team] => Vendetta
            [home_score] => 8
            [date] => Sep 22, 2011
        )

    [1] => Array
        (
            [away_team] => Dirty Mike and the Boys
            [away_score] => 5
            [home_team] => Bottom Feeders
            [home_score] => 1
            [date] => Sep 22, 2011
        )

## Other Examples

### Scraping an element that doesn't have a identifier

	$html = $wp->scrape_snippet('#pg_content');
	$json = $wp->element_to_json('table', array('html'=>$html));
	
### Find the third table element on a page

	$html = $wp->scrape_snippet('table', array('offset' => 3));
	
### Get Array from third table element on a page
	
	$html = $wp->scrape_snippet('table', array('offset'=>3));
	$json = $wp->element_to_array('table');
