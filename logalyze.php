<?php
	error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED); ini_set('display_errors', 1);

	if(!isset($_GET['password']) || $_GET['password'] !=='') {
		exit('Password Required');
	}

	require_once('../config.php');
///////////// FILTERS /////////////////////
	if(!isset($_GET['pad']) || (isset($_GET['pad']) && $_GET['pad']==1)) {
		$filters = array(
			array(
				array('field' => 'type',
					'op' => '=',
					'data' => 'info'
				)
			)
		);
	}

	
	if(isset($_GET['filters'])) {
		$filters = json_decode($_GET['filters'], true);
	}

	if(isset($_GET)) {
		//Filter out certain tags
		foreach($_GET as $key => $val) {
			if(!in_array($key, array('filters','sort','password','date'))) {
				$filters[0][] = array('field' => $key,
					'op' => '=',
					'data' => $val
				);
			}
		}
	}



	$sort = array('field' => 'timetocomplete',
		'op' => '>');
	if(isset($_GET['sort'])) {
		$sort['field'] = $_GET['sort'];
	}

	$date = '' ?: date('Y-m-d');
	if($_GET['date']) {
		$date = $_GET['date'];
	}
	$filename = DIR_LOGS . ALIAS . '\\' . $date . '\\' . $date . '.log';
	//$filename = DIR_LOGS . ALIAS . '\\2020-12-31\\2020-12-31.log';
///////////// END FILTERS ////////////////




	$lines = explode("\n\r\n", file_get_contents($filename));

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
						if(isset($line['timetocomplete'])) {
							$timeSum += floatval($line['timetocomplete']);
						}

						continue 2;
					}
				}
			} else {
				$extrude[] = $line;
				if(isset($line['timetocomplete'])) {
					$timeSum += floatval($line['timetocomplete']);
				}
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

		print_r("<b style='font-size:0.9em;'>Your IP: " . $_SERVER['REMOTE_ADDR'] . '<br/>');
		echo("File: " . $filename . '<br/>');
		?>
		Filters: <br/>
			<form action="/exec/logalyze.php" method="GET">
				<input type="hidden" value="<?=$_GET['password'] ?>" name="password" />
				<input type="hidden" value="<?=$_GET['sort'] ?>" name="sort" />
				<textarea name="filters" cols="100" rows="20"><?print_r(json_encode($filters, JSON_PRETTY_PRINT)); ?></textarea>
				<br/>
				<input type="submit" /><input type="submit" value="&#x29c9;" formtarget="_blank" />
				<input type="date" value="<?=$date?>" name="date" />
			</form>
		<?
		echo "<br/>Sorting:";
		if($sort['field'] == 'timetocomplete') {?>
			timetocomplete -> 
			<a href="<?=$_SERVER['REQUEST_URI']?>&sort=timestamp">timestamp</a>
		<? } else { ?>
			timestamp -> 
			<a href="<?=$_SERVER['REQUEST_URI']?>&sort=timetocomplete">timetocomplete</a>
		<? }

		echo "<br/>Total Timetocomplete: <br/>";
		echo $timeSum . 's<br/>';

		echo "<br/>Average Timetocomplete: <br/>";
		echo ($timeSum/count($extrude)) . 's<br/><br/>';

		echo "<br/><br/>Log Data (".count($extrude)." events):</b><br/><br/>";
		//print_r(json_encode($extrude, JSON_PRETTY_PRINT));
		echo "<pre>";
		foreach($extrude as $event) { 
			$url = $_SERVER['REQUEST_URI'];
		?>
<div style="background-color:#FAFAFA; margin:1em;"><a href="<?=$url.'&timestamp='.$event['timestamp']?>"><b>timestamp:</b> <?=$event['timestamp'] ?></a>		<a href="<?=$url.'&type='.$event['type']?>"><b>type:</b> <?=$event['type'] ?></a>	
<a href="<?=$url.'&timetocomplete='.$event['timetocomplete']?>"><b>timetocomplete:</b> <?=$event['timetocomplete'] ?></a>		<a href="<?=$url.'&token='.$event['token']?>"><b>token:</b> <?=$event['token'] ?></a>		<a href="<?=$url.'&url='.urlencode($event['url'])?>"><b>url:</b> <?=$event['url'] ?></a><br/><? 
if($event['type'] == 'fbapi') {
	$event['data']['Rq'] = json_decode($event['data']['Rq']);
}
print_r(str_replace(array('\\\\', '\\n', '\\r', '\\t'), array("\\", "\n", '', "\t"), json_encode($event['data'], JSON_PRETTY_PRINT))); 

?></div><?
		}


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

			if(is_array($line)) {
				$line = json_encode($line);
			}

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
				case 'missing':
					return strpos($line, $group['data']) === false;
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
