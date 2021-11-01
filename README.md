# Logalyze
A simple script for analyzing logs and aggregating data. Very useful for profiling and doing benchmarks on your site by leveraging your application logs.

1.1)	Structure
Each log file is full of log events. Each event is delimited by a new line and is formatted as a json object. This allows us to pull out specific fields and data about each event.
{
  "timestamp": "2020-02-16 21:46:48",
  "type": "info",
  "token": "5e49fe977f2b2",
  "url": "/index.php?route=account/login",
  "timetocomplete": 1.2003221511841,
  "data": {
    "info": "Exit index.php"
  }
}
Each event is accompanied by a set of metadata fields, which are all important for profiling the performance of the script.
-	timestamp is useful for filtering to a specific time when an event occurred (Always EST)
-	token is a unique id created for every page load. All events with the same token occurred during the same page load
-	Timetocomplete is the length of time required to complete the event in question measured in seconds
-	url is the page load that was occurring when the event happened
-	type is important for discerning between different events:
		Info is a general log event
		mysql is a sql query done using a direct connection to a mysql database
This consists of both calls to our local databases and calls to remote Fishbowl databases. Right now there is no easy way to filter between which is which.
fbapi are requests to the fishbowl api
		fbdb is a sql query that is run using the fishbowl api “ExecuteQueryRq”
This way of querying the fishbowl database is considerably slower than the direct connection method. It has been mostly phased out across Vortex so this type of event is rare to see in the logs at all.
error is another rare event to see in the logs, but it records anything deemed an error by the site (e.g. try catch statements) *Not very useful right now
-	data is the actual content of the log. It defines what occurred and the format of it changes based on the event type.
e.g. fbapi events always have a Rq and Rs field to show the request and response to Fishbowl
		
1.2)	exec/logalyze.php
logalyze.php is a simple php script that I wrote to make searching through and profiling log events faster and easier. 
Using the script requires opening the source code for it and editing the filters at the beginning of the file. Right now, this is only setup for LMW & Vortex, but the file can easily be copied into the other sites.
Running the script is simple; just visit the page on the site that you want to profile and it will display the results of what you put in the source code to filter:
https://demo.lp4fb.com/exec/logalyze.php

1.2.2.1) Filtering data
You can filter data based on any of the top-level fields in our log files. To add a filter, the $filters variable needs to have this structure:
	$filters = array(
		array(
			array('field' => 'type',
				'op' => '=',
				'data' => 'info'
			 )
		)
	);
There are 3 levels of arrays allowing you to have multiple filters.
-	The top level array can hold multiple middle level arrays. Each middle level array within constitutes a logical OR
-	The middle level arrays can hold multiple bottom level arrays. Each bottom level array within constitutes a logical AND
-	The bottom level arrays are a single filter. Only events that match the criteria will be displayed.
o	In the example above are 3 required fields:
	‘field’ specifies the top-level field in the log event that will be filtered on
•	* is a special field for comparing the entire event text. If you use that for your field, you can search the entire line
	‘data’ is the info that the ‘field’ will be compared to
	‘op’ specifies the comparison operation that the field will be filtered by:
•	=	equals
•	!=	not equal
•	>	greater than
•	<	less than
•	>=	greater than or equal to
•	<=	less than or equal to 
•	contains 	used to search a field (is true if the field contains the data string)
•	regex 	similar to ‘contains’ but you can use a regular expression to filter out a field

In the above sample, this filter will only display log events that have a type field equal to ‘info’:
 

LOGICAL AND
This setup shows 2 bottom-level filters in the middle level array.
	$filters = array(
		array(
			array('field' => 'type',
				'op' => '=',
				'data' => 'fbapi'
			),
			array('field' => 'token',
				'op' => '=',
				'data' => '5e6f8168b06df'
			)
		)
	);
In this example, it will show us all the fishbowl calls that occurred during a specific page load.
So, we’re filtering by both the type and the token.


LOGICAL OR
This filter shows two middle level arrays  in the top-level array. Each middle level filter has its own single filter.
	$filters = array(
		array(
			array('field' => 'timestamp',
				'op' => '>',
				'data' => '2020-03-16 08:38:00'
			)
		), 
		array(
			array('field' => 'timestamp',
				'op' => '<',
				'data' => '2020-03-16 05:30:00'
			)
		)
	);
In this example, you can see a logical OR setup that will return all events that occurred after 8:38AM or before 5:30AM. This would leave out any events that occurred between these times.


1.2.2.2)	 Sorting
The sort variable is important for finding events that take a long time or viewing events based on timestamp.
	$sort = array(
'field' => 'timetocomplete',
		'op' => '>'
);
The field lets you sort by timetocomplete and the operation lets you determine the direction. This example will show the slowest log event to complete at the top of the page first thing.
You can change the field to timestamp to order by the time that the events took place and switch out the operator to be in ascending order (< less than).

1.2.2.3) Filename
The filename variable is just the log file that is being searched through.
$filename = DIR_LOGS . ALIAS . '\\' . date('Y-m-d') . '\\' . date('Y-m-d') . '.log';
You may need to change it if you want to look through a different date when there is a better sample of events.
By default, it is usually set to the current date.
