<?php
/*
 * different event formats - irrelevant
 * time info - irrelevant if sorted as string
 * order by time, type (G>Y>R>S), team, player1
 */

/* Example input
abc
cba
2
mo sa 45+2 Y
a 13 G
2
d 23 S f
z 46 G
*/

/* Example output
abc a 13 G
cba d 23 S f
abc mo sa 45+2 Y
cba z 46 G
*/
function comparison ( $event1, $event2 ) {
    foreach ( ['time', 'type', 'team', 'player'] as $by ) {
        $compared = $event1[$by] <=> $event2[$by];
        if ( $compared != 0 ) return $compared;
    }
    return 0;
}

function getEventsOrder ( string $teamNameA, string $teamNameB, array $eventsTeamA, array $eventsTeamB ) {
    $events = array_merge(
        parseTeamEvents( $teamNameA, $eventsTeamA ),
        parseTeamEvents( $teamNameB, $eventsTeamB )
    );
    usort( $events, 'comparison' );
    return array_map( function ( $a ) {
        return "{$a['team']} {$a['raw']}";
    }, $events );
}

function parseTeamEvents ( $team, $events ) {
    $parsed = [];
    foreach ( $events as $event ) {
        preg_match( "#(?<player>[\w\s]+?)\s(?<time_plus>\d+(\+\d+)?)\s(?<type>[GYRS])\s?#", $event, $values );
        $parsed [] = [
            'raw' => $event,
            'team' => $team,
            'time' => $values['time_plus'],
            'type' => [
                'G' => 1,
                'Y' => 2,
                'R' => 3,
                'S' => 4,
            ][$values['type']],
            'player' => $values['player'],
        ];
    }
    return $parsed;
}

#region default read code
$fptr = fopen( "out.txt", "w" );
$read = fopen( 'in.txt', "r+" );
$team1 = rtrim( fgets( $read ), "\r\n" );
$team2 = rtrim( fgets( $read ), "\r\n" );
$events1_count = intval( trim( fgets( $read ) ) );
$events1 = array ();
for ( $i = 0; $i < $events1_count; $i++ ) {
    $events1_item = rtrim( fgets( $read ), "\r\n" );
    $events1[] = $events1_item;
}
$events2_count = intval( trim( fgets( $read ) ) );
$events2 = array ();
for ( $i = 0; $i < $events2_count; $i++ ) {
    $events2_item = rtrim( fgets( $read ), "\r\n" );
    $events2[] = $events2_item;
}
$result = getEventsOrder( $team1, $team2, $events1, $events2 );
fwrite( $fptr, implode( "\n", $result ) . "\n" );
fclose( $fptr );
#endregion default read code