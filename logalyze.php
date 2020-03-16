<?php
	error_reporting(E_ALL); ini_set('display_errors', 1);
	//require_once('config\paths.php');

/////////////////////  FILTERS  /////////////////

	if(isset($_GET['filter'])) {
		$filters = $_GET['filter'];
	}

	$filters = array(
		array(
			array('field' => 'timetocomplete',
				'op' => '>',
				'data' => '1.0'
			)
		)
	);

	if(isset($_GET['sort'])) {
		$sort = $_GET['sort'];
	}
	$sort = array('field' => 'timetocomplete',
		'op' => '>');

	$site = parse_url($_SERVER['SERVER_NAME']);
	$alias = explode('.', $site['path'])[0];
	$filename = LOGS . strtoupper($alias) . '/pond.log.json';


	$tailLength = 2000000;
	if(isset($_GET['tail'])) {
		$tailLength = intval($_GET['tail']);
	}

//////////////////  End Filters  ///////////////////////

	$lines = explode("\r\n", tailCustom($filename, -$tailLength));

	$extrude = array();
	foreach($lines as $i => $l) {
		$line = json_decode($l, true);

		if(isset($filters)) {
			foreach($filters as $filter) {
				$flag = true;
				foreach($filter as $group) {
					if(!compare($group, $line)) {
						$flag = false;
						continue 2;
					}
				}
				if($flag) {
					$extrude[] = $line;
					continue 2;
				}
			}
		} else {
			$extrude[] = $line;
		} 


	}
	if(isset($sort)) {
		usort($extrude, function($a, $b) use($sort) {
			if(isset($a[$sort['field']]) && isset($b[$sort['field']])) {
				$a = $a[$sort['field']];
				$b = $b[$sort['field']];

				$group = array('data' => $a,
					'op' => $sort['op'],
					'field' => '*');
				return compare($group, $b); 
			}
		});
	}


	echo "<pre><b style='font-size:0.9em;'>";
	echo("File: " . $filename . '<br/>Filters:<br/>');
	print_r(json_encode($filters, JSON_PRETTY_PRINT));
	echo "<br/>Sorting: <br/>";
	print_r(json_encode($sort, JSON_PRETTY_PRINT));

	echo "<br/><br/>Log Data (".count($extrude)." events):</b><br/><br/>"; 
	print_r(json_encode($extrude, JSON_PRETTY_PRINT));




function compare($group, $line) {
	if(isset($group['field']) && isset($group['data'])) {
		if($group['field'] == '*') {
			if(is_array($line)) {
				$line = json_encode($line);
			}
		}
		else if(isset($line[$group['field']])) {
			$line = $line[$group['field']];
		} else {
			return false;
		}

		$operator = isset($group['op']) ? $group['op'] : '=';

		switch($operator) {
			case '=':
				return $line == $group['data'];
				break;
			case '!=':
				return $line != $group['data'];
				break;
			case '<':
				return $line < $group['data'];
				break;
			case '>':
				return $line > $group['data'];
				break;
			case '<=':
				return $line <= $group['data'];
				break;
			case '>=':
				return $line >= $group['data'];
				break;
			case 'contains':
				return strpos($line, $group['data']) !== false;
				break;
			case 'regex':
				return preg_match("/${group['data']}/i", $line);
				break;
		}
	}
	return false;
}

function sortCompare($a, $b) {
	if(isset($a[$sort['field']]) && isset($b[$sort['field']])) {
		$a = $a[$sort['field']];
		$b = $b[$sort['field']];

		$group = array('data' => $a,
			'op' => $sort['op'],
			'field' => '*');
		return compare($group, $b); 
	}
}

function tailCustom($filepath, $chars = -10000) {
	$f = @fopen($filepath, "r");
	fseek($f, $chars, SEEK_END);
	$data = fread($f, abs($chars));
	fclose($f);
	$pos = strpos($data, "\n");
	if ($pos !== false) {
	    $data = substr($data, $pos+1);
	}
	return trim($data);
}

?>
